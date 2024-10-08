<?php

namespace App\Presenters;

use Nette\Utils\Strings;


/**
 * Cron every 5 or 10 minutes
 */
class CronPresenter extends BaseBasePresenter
{

        private const API_TYPE_PUBLIC = 1;
        private const API_TYPE_APP = 2;
        private const API_TYPE = self::API_TYPE_PUBLIC;

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
                                // devicetoken and vendorid are not required anymore - no more decrypting of HTTPS communication
				'devicetoken' => 'devicetoken',//$user->devicetoken,
				'vendorid' => 'vendorid',//$user->vendorid,
				'phoneid' => $user->phoneid,
			);

			// Make parameters for query
			$deviceids = '';
			$measurementfroms = '';
			$measurementcounts = '';
			foreach ($devicesDb as $device) {
				$deviceids .= $device->id . ',';
				$measurementfroms .= $device->related('measurement')->max('ts') + 1 . ','; // Works for empty table too
				$measurementcounts .= '1000';
			}

			// Remove last comma from string
			$measurementfroms = rtrim($measurementfroms, ',');
			$measurementcounts = rtrim($measurementcounts, ',');

                        if (self::API_TYPE == self::API_TYPE_APP) {
                            // GET data from server - use the same query as android app
                            // this endpoint is not working anymore - TODO: sniff it from app
                            $curl = curl_init("www.data199.com:8080/api/v1/dashboard");
                        } else {
                            $curl = curl_init("https://www.data199.com/api/pv1/device/lastmeasurement");
                        }
			curl_setopt_array($curl, array(
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_HTTPHEADER => array('Content-Type' => 'application/x-www-form-urlencoded', 'charset' => 'utf-8', 'Connection' => 'Keep-Alive'),
				CURLOPT_USERAGENT => 'Dalvik/1.6.0 (Linux; U; Android 4.3; Galaxy Nexus Build/JWR66Y)', // Any user-agent, for example Nexus phone :)
				CURLOPT_ENCODING => 'gzip',
				CURLOPT_POSTFIELDS => $this->getQuery($deviceids, $measurementfroms, $measurementcounts, $phoneInfo),
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_CONNECTTIMEOUT => 10,
				CURLOPT_TIMEOUT => 45,
			));
			$json = curl_exec($curl);

			if ($json === FALSE) {
				echo 'Curl error: ' . curl_error($curl);
                                curl_close($curl);
				$this->terminate();
			}
                        curl_close($curl);

			// Convert data from json to array
			$data = json_decode($json);
			unset($json);

			/*
			 * RESPONSE STRUCTURE
			 * 
			 * More details including types of devices can be found in REST API documentation.
			 * https://mobile-alerts.eu/info/public_server_api_documentation.pdf
			 * 
			 * idx - Unique id of the measurement
			 * ts  - Timestamp of the measurement in epoch time
			 * tx  - ID of measurement unique for each device
			 * c   - Timestamp when the measurement was received by the server
			 * 
			 * t1  - The measured temperature in celsius
			 * t2  - The measured temperature in celsius of the cable / external sensor
			 * h   - The measured humidity
			 * 
			 * r:	The rain value in mm, 0.258 mm of rain are equal to one flip
			 * 
			 * ws:	The measured windspeed in m/s
			 * wg:	The measured gust in m/s
			 * wd:	The wind direction
			 * 	0: North, 1: North-northeast, 2: Northeast, 3: East-northeast
			 *	4: East, 5: East-southeast, 6: Southeast, 7: South-Southeast
			 *	8: South, 9: South-southwest, 10: Southwest, 11: West-southwest
			 *	12:West, 13: West-northwest, 14: Northwest, 15: Northnorthwest
			 */

			// Example dumps of interesting parts of response
//			dump($data);
//			dump($data->result->devices);
//			dump($data->result->devices[0]->measurements);
//                      $this->terminate();
//                      
			// Create array of important values for database
			$insertArr = array();
			if (self::API_TYPE == self::API_TYPE_APP) {
                            if (!isset($data->result)) {
                                    continue;
                            }
                            foreach ($data->result->devices as $device) {
    //				dump($device->measurements);

                                $deviceDb = $devicesDb->get($device->deviceid);
                                
                                // set correct devicetypeid here (use 0 when creating new device in DB)
                                if($device->devicetypeid != $deviceDb->type){
                                        $deviceDb->update(['type' => $device->devicetypeid]);
                                }

                                // update device name if user want to (names are probably synchronized with chosen phoneid - androidApp)
                                if ($user->updatenames == 1) {
                                        if($device->name != $deviceDb->name){
                                                $deviceDb->update(['name' => $device->name]);
                                        }
                                }

                                // Store measurements
                                foreach ($device->measurements as $measurement) {
                                        // Common values
                                        $insertRow = array(
                                                'device_id' => $device->deviceid,
                                                'ts' => $measurement->ts,
                                                't1' => NULL,
                                                't2' => NULL,
                                                'h' => NULL,
                                                'r' => NULL,
                                                'ws' => NULL,
                                                'wg' => NULL,
                                                'wd' => NULL,
                                        );

                                        // Tested devices: 2, 3, 8, 11
                                        // Also added < 10 to list according to REST API documentation, not sure about >= 10 and letter A+

                                        // Temperature1
                                        if (in_array($device->devicetypeid, $this->deviceTypeList['t1'])) {
                                                $insertRow['t1'] = $measurement->t1;
                                        }

                                        // Temperature2
                                        if (in_array($device->devicetypeid, $this->deviceTypeList['t2'])) {
                                                $insertRow['t2'] = $measurement->t2;
                                        }

                                        // Humidity
                                        if (in_array($device->devicetypeid, $this->deviceTypeList['h'])) {
                                                $insertRow['h'] = $measurement->h;
                                        }

                                        // Rain in mm
                                        if (in_array($device->devicetypeid, $this->deviceTypeList['r'])) {
                                                $insertRow['r'] = $measurement->r;
                                        }

                                        // Wind
                                        // device type should be 'ID0B', but it is '11', not sure how will be '11' seen
                                        if (in_array($device->devicetypeid, $this->deviceTypeList['wsgd'])) {
                                                $insertRow['ws'] = $measurement->ws;
                                                $insertRow['wg'] = $measurement->wg;
                                                $insertRow['wd'] = $measurement->wd;

                                        }

                                        // Insert row into array
                                        $insertArr[] = $insertRow;
                                }
                            }
                        } else {
                            if (!isset($data->devices)) {
                                    continue;
                            }
                            foreach ($data->devices as $device) {
    //				dump($device->measurements);

                                $deviceDb = $devicesDb->get($device->deviceid);

                                if ($deviceDb->type == 0) {
                                    echo 'Device with type = 0 -> can not determine available values';
                                }

                                if (!isset($device->measurement)) {
                                    // e.g. rain sensor after a while (1m) without rain and thus without measurements
                                    continue;
                                }

                                // Store measurement
                                $measurement = $device->measurement;

                                // Common values
                                $insertRow = array(
                                        'device_id' => $device->deviceid,
                                        'ts' => $measurement->ts,
                                        't1' => NULL,
                                        't2' => NULL,
                                        'h' => NULL,
                                        'r' => NULL,
                                        'ws' => NULL,
                                        'wg' => NULL,
                                        'wd' => NULL,
                                );

                                // Tested devices: 2, 3, 8, 11
                                // Also added < 10 to list according to REST API documentation, not sure about >= 10 and letter A+

                                // Temperature1
                                if (in_array($deviceDb->type, $this->deviceTypeList['t1'])) {
                                        $insertRow['t1'] = $measurement->t1;
                                }

                                // Temperature2
                                if (in_array($deviceDb->type, $this->deviceTypeList['t2'])) {
                                        $insertRow['t2'] = $measurement->t2;
                                }

                                // Humidity
                                if (in_array($deviceDb->type, $this->deviceTypeList['h'])) {
                                        $insertRow['h'] = $measurement->h;
                                }

                                // Rain in mm
                                if (in_array($deviceDb->type, $this->deviceTypeList['r'])) {
                                        $insertRow['r'] = $measurement->r;
                                }

                                // Wind
                                // device type should be 'ID0B', but it is '11', not sure how will be '11' seen
                                if (in_array($deviceDb->type, $this->deviceTypeList['wsgd'])) {
                                        $insertRow['ws'] = $measurement->ws;
                                        $insertRow['wg'] = $measurement->wg;
                                        $insertRow['wd'] = $measurement->wd;

                                }

                                // Insert row into array
                                $insertArr[] = $insertRow;

                            }
                        }

//                        dump($insertArr);
			// Insert and ignore existing (PRIMARY KEY = deviceid+ts)
			$this->database->query('INSERT IGNORE INTO measurement ?', $insertArr);
		}

		echo 'ok';
		$this->terminate();
	}

	private function getQuery($deviceids, $measurementfroms, $measurementcounts, $phoneInfo)
	{
            if (self::API_TYPE == self::API_TYPE_APP) {
		$paramsArr = array(
			'devicetoken' => $phoneInfo['devicetoken'],
			'vendorid' => $phoneInfo['vendorid'],
			'phoneid' => $phoneInfo['phoneid'],
			'version' => '1.38',
			'build' => '137',
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
			'measurementcounts' => $measurementcounts, // number of values for each device - without ',' at the end
		);

		$query = '';
		foreach ($paramsArr as $key => $value) {
			if ($key == 'requesttoken') {
				// Create hash
				$md5 = md5(Strings::lower(Strings::replace(rtrim($query, '&') . 'uvh2r1qmbqk8dcgv0hc31a6l8s5cnb0ii7oglpfj', '~[-,\.]~', ''))); // remove from string: - , . and lowercase
				$query .= $key . '=' . $md5;
			} else {
				$query .= $key . '=' . $value;
			}
			$query .= '&';
		}

		// remove last char and return query
		return rtrim($query, '&');
            } else {
                return 'deviceids' . '=' . $deviceids;
            }
	}

}
