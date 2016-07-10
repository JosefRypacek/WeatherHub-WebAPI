<?php

namespace App\Presenters;

use Nette\Utils\Strings;


/**
 * Cron every 5 or 10 minutes
 */
class CronPresenter extends BaseBasePresenter
{

	/** @var \Nette\DI\Container @inject */
	public $container;

	protected function startup()
	{
		parent::startup();

		$httpRequest = $this->container->getByType('Nette\Http\Request');
		if ($httpRequest->getRemoteAddress() != $_SERVER['SERVER_ADDR']) {
			echo 'Only local requests are allowed!';
			$this->terminate();
		}
	}

	public function actionDefault()
	{

		// Select devices and last stored measurement (ts + device_id) for each user
		foreach ($this->database->table('user') as $user) {
			$devicesDb = $user->related('device');

			// Get user personal values for query
			$phoneInfo = array(
				'devicetoken' => $user->devicetoken,
				'vendorid' => $user->vendorid,
				'phoneid' => $user->phoneid,
			);

			// Make parameters for query
			$deviceids = '';
			$measurementfroms = '';
			foreach ($devicesDb as $device) {
				$deviceids .= $device->id . ',';
				$measurementfroms .= $device->related('measurement')->max('ts') + 1 . ','; // Works for empty table too
			}

			// Remove last comma from string
			$measurementfroms = rtrim($measurementfroms, ',');

			// GET data from server - the same query as android app
			$curl = curl_init("www.data199.com:8080/api/v1/dashboard");
			curl_setopt_array($curl, array(
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_HTTPHEADER => array('Content-Type' => 'application/x-www-form-urlencoded', 'charset' => 'utf-8', 'Connection' => 'Keep-Alive'),
				CURLOPT_USERAGENT => 'Dalvik/1.6.0 (Linux; U; Android 4.3; Galaxy Nexus Build/JWR66Y)', // Any user-agent, for example Nexus phone :)
				CURLOPT_ENCODING => 'gzip',
				CURLOPT_POSTFIELDS => $this->getQuery($deviceids, $measurementfroms, $phoneInfo),
				CURLOPT_RETURNTRANSFER => true,
			));
			$json = curl_exec($curl);
			curl_close($curl);

			if ($json === FALSE) {
				echo 'failed';
				$this->terminate();
			}

			// Convert data from json to array
			$data = json_decode($json);
			unset($json);

			/*
			 * RESPONSE STRUCTURE
			 * 
			 * idx - ID of ???
			 * ts  - Time of measurement
			 * tx  - ID of measurement unique for each device
			 * c   - Time of data upload ???
			 * t1  - temperature
			 * h   - humidity
			 */

			// Example dumps of interesting parts of response
			//dump($data);
//			dump($data->result->devices);
			//dump($data->result->devices[0]->measurements);
			// Create array of important values for database
			$insertArr = array();
			if (!isset($data->result)) {
				continue;
			}
			foreach ($data->result->devices as $device) {
				// auto update of name stored in DB
				$deviceDb = $devicesDb->get($device->deviceid);
				if ($device->name != $deviceDb->name) {
					$deviceDb->update(['name' => $device->name]);
				}
				// Store measurements
				foreach ($device->measurements as $measurement) {
					// Common values for devicetypeid 2 & 3
					$insertRow = array(
						'device_id' => $device->deviceid,
						'ts' => $measurement->ts,
						't1' => $measurement->t1,
						'h' => NULL, // needed!
					);
					// Humidity for devicetypeid 3
					if ($device->devicetypeid == 3) {
						$insertRow['h'] = $measurement->h;
					}
					// Insert row into array
					$insertArr[] = $insertRow;
				}
			}

			// Insert and ignore existing (PRIMARY KEY = deviceid+ts)
			$this->database->query('INSERT IGNORE INTO measurement ?', $insertArr);
		}

		echo 'ok';
		$this->terminate();
	}

	private function getQuery($deviceids, $measurementfroms, $phoneInfo)
	{
		$paramsArr = array(
			'devicetoken' => $phoneInfo['devicetoken'],
			'vendorid' => $phoneInfo['vendorid'],
			'phoneid' => $phoneInfo['phoneid'],
			'version' => '1.24',
			'build' => '84',
			'executable' => 'eu.mobile_alerts.weatherhub',
			'bundle' => 'eu.mobile_alerts.weatherhub',
			'lang' => 'cs',
			'timezoneoffset' => '60', // 60 in winter time... 120 in summer time...  But DON'T CARE! We just need unix time.
			'timeampm' => 'false',
			'usecelsius' => 'true',
			'usemm' => 'true',
			'speedunit' => '0',
			'timestamp' => time(), // this (and previous items) cant be changed without requesttoken
			// stop hashing...
			'requesttoken' => '--- automatically generated ---',
			'deviceids' => $deviceids, // format: '123,123,123,'
			'measurementfroms' => $measurementfroms, //format: ',,' // timestamp FROM (ts) for each device - without ',' at the end
			'measurementcounts' => '1000,1000,1000,1000,1000', // number of values for each device - without ',' at the end
		);

		$query = '';
		foreach ($paramsArr as $key => $value) {
			if ($key == 'requesttoken') {
				// Create hash
				$md5 = md5(Strings::lower(Strings::replace(rtrim($query, '&') . 'asdfaldfjadflxgeteeiorut0ß8vfdft34503580', '~[-,\.]~', ''))); // remove from string: - , . and lowercase
				$query .= $key . '=' . $md5;
			} else {
				$query .= $key . '=' . $value;
			}
			$query .= '&';
		}

		// remove last char and return query
		return rtrim($query, '&');
	}

}
