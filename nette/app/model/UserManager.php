<?php

declare(strict_types=1);

namespace App\Model;

use Nette;
use Nette\Security\Passwords;


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


	public function __construct(Nette\Database\Context $database, Passwords $passwords)
	{
		$this->database = $database;
		$this->passwords = $passwords;
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

		if (!$row) {
			throw new Nette\Security\AuthenticationException('The username is incorrect.', self::IDENTITY_NOT_FOUND);

		} elseif (!$this->passwords->verify($password, $row[self::COLUMN_PASSWORD_HASH])) {
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