<?php

declare(strict_types=1);

namespace App\Model;

use Nette;
use Nette\Security\Passwords;

class LoginProtectionException extends \Exception
{
}

/**
 * Users management.
 */
final class UserManager implements Nette\Security\IAuthenticator
{

	private const
		TABLE_NAME = 'user',
		COLUMN_ID = 'id',
		COLUMN_NAME = 'username',
		COLUMN_PASSWORD_HASH = 'password',
		COLUMN_ROLE = 'role';


	/** @var Nette\Database\Context */
	private $database;

	/** @var Passwords */
	private $passwords;

        /** @var Nette\Http\Request */
	public $httpRequest;

	public function __construct(Nette\Database\Context $database, Passwords $passwords, Nette\Http\Request $httpRequest)
	{
		$this->database = $database;
		$this->passwords = $passwords;
                $this->httpRequest = $httpRequest;
	}


	/**
	 * Performs an authentication.
	 * @throws Nette\Security\AuthenticationException
	 */
	public function authenticate(array $credentials): Nette\Security\IIdentity
	{
		[$username, $password] = $credentials;

		$row = $this->database->table(self::TABLE_NAME)
			->where(self::COLUMN_NAME, $username)
			->fetch();

                $ip = $this->httpRequest->getRemoteAddress();
                $this->loginProtectionCheck($row, $ip);

		if (!$row) {
                        $this->loginProtectionAdd($ip);
			throw new Nette\Security\AuthenticationException('The username is incorrect.', self::IDENTITY_NOT_FOUND);

		} elseif (!$this->passwords->verify($password, $row[self::COLUMN_PASSWORD_HASH])) {
                        $this->loginProtectionAdd($ip, $row->id);
			throw new Nette\Security\AuthenticationException('The password is incorrect.', self::INVALID_CREDENTIAL);

		} elseif ($this->passwords->needsRehash($row[self::COLUMN_PASSWORD_HASH])) {
			$row->update([
				self::COLUMN_PASSWORD_HASH => $this->passwords->hash($password),
			]);
		}

		$arr = $row->toArray();
		unset($arr[self::COLUMN_PASSWORD_HASH]);
		return new Nette\Security\Identity($row[self::COLUMN_ID], $row[self::COLUMN_ROLE], $arr); // set role
	}
	
	public function setPassword($id, $password): void
	{
		$this->database->table(self::TABLE_NAME)
				->where(array(self::COLUMN_ID => $id))
				->update(array(
					self::COLUMN_PASSWORD_HASH => $this->passwords->hash($password),
		));
	}

        /*
         * Methods below are for simple brute force password protection with minimal changes to each app.
         * It allows to lockout user when there are many attempts for this user (IP doesn't play role here).
         * It allows to lockout IP when there are many attempts from this IP for any user.
         * There is a history of failed attempts up to 24 hours.
         * The code is not optimal and issues many SQL queries.
         * 1. Check if client is allowed to login (based on its IP or target user).
         * 2. Add DB entry on failed attempt (with or without user ID).
         *
         * Based on arguments it would check for:
         * A) attempts based on IP
         *  10 failed IP tries in last 60 minutes
         *  20 failed IP tries in last 120 minutes
         *  30 failed IP tries in last 240 minutes
         *  40 failed IP tries in last 480 minutes
         *  50 failed IP tries in last 960 minutes
         *
         * B) attempts based on target user ID
         *   5 failed account tries in last 15 minutes
         *  10 failed account tries in last 30 minutes
         *  15 failed account tries in last 60 minutes
         *  20 failed account tries in last 120 minutes
         *  25 failed account tries in last 240 minutes
         *  30 failed account tries in last 480 minutes
         *  35 failed account tries in last 960 minutes
         */

        private function loginProtectionCheck($userRow, $ip): void
        {
            // Prune old attempts.
            $yesterday = new Nette\Utils\DateTime();
            $yesterday->sub(new \DateInterval('PT24H'));
            $this->database->table('loginprotection')->where(['date <' => $yesterday])->delete();

            // First check IP - no matter if the user name exists or not.
            $loginAttemptIpDb = $this->database->table('loginprotection')->where(['ip' => $ip]);
            if ($this->loginProtectionCondition($loginAttemptIpDb, 10, 60)) {
                throw new LoginProtectionException('Příliš mnoho pokusů o přihlášení. Zkuste to prosím později.');
            }

            // Then check user - only if username is valid.
            if ($userRow) {
                $loginAttemptUser = $this->database->table('loginprotection')->where(['user_id' => $userRow->id]);
                if ($this->loginProtectionCondition($loginAttemptUser, 5, 15)) {
                    throw new LoginProtectionException('Příliš mnoho pokusů o přihlášení. Zkuste to prosím později.');
                }
            }
        }

        private function loginProtectionAdd($ip, $userId = null): void
        {
            $this->database->table('loginprotection')->insert(
                    ['ip' => $ip, 'user_id' => $userId, 'date' => new Nette\Utils\DateTime()]
                );
        }

        private function loginProtectionCondition($numberOfAttemptsDb, $baseAttempts, $baseMinutes)
        {
            $i = 1;
            while (true) {
                $pastMinutes = pow(2, $i-1) * $baseMinutes;
                if ($pastMinutes > 1440) {
                    // maximum 1 day
                    break;
                }

                $pastDate = new Nette\Utils\DateTime();
                $pastDate->sub(new \DateInterval('PT' . $pastMinutes . 'M'));

                $numberOfAttempts = (clone $numberOfAttemptsDb)->where(['date >' => $pastDate])->count();

                if ($numberOfAttempts > $baseAttempts * $i) {
                    return true;
                }

                $i++;
            }

            return false;
        }

	/**
	 * Adds new user.
	 * @throws DuplicateNameException
	 */
//	public function add(string $username, string $email, string $password): void
//	{
//		Nette\Utils\Validators::assert($email, 'email');
//		try {
//			$this->database->table(self::TABLE_NAME)->insert([
//				self::COLUMN_NAME => $username,
//				self::COLUMN_PASSWORD_HASH => $this->passwords->hash($password),
//			]);
//		} catch (Nette\Database\UniqueConstraintViolationException $e) {
//			throw new DuplicateNameException;
//		}
//	}
}



class DuplicateNameException extends \Exception
{
}