<?php

	/**
	 * SMF Authentication
	 *
	 * @author Jeremy Darwood
	 * @license BSD
	 * @copyright: 2011 Jeremy Darwood
	 *
	 * @version 0.1
	 * @package auth_smf
	 * @subpackage core
	 */

	/**
	 * SMF Authentication
	 *
	 * @package auth_smf
	 * @subpackage core
	 */
	class SMFAuth extends TBGModule
	{
		protected $_longname = 'SMF Authentication';
		protected $_description = 'Allows authentication with a working SMF 2.0+ installtion';
		protected $_module_config_title = 'SMF Authentication';
		protected $_module_config_description = 'Configure server connection settings';
		protected $_module_version = '0.1';
		protected $_has_config_settings = true;

		/**
		 * Return an instance of this module
		 *
		 * @return SMF Authentication
		 */
		public static function getModule()
		{
			return TBGContext::getModule('auth_smf');
		}

		protected function _initialize()
		{
		}
		
		protected function _addRoutes()
		{
			$this->addRoute('smf_login', '/login/smf', 'autoLogin');

		}

		protected function _install($scope)
		{
		}

		protected function _uninstall()
		{
		}
		
		public final function getType()
		{
			return parent::MODULE_AUTH;
		}

		public function getRoute()
		{
			return TBGContext::getRouting()->generate('smf_authentication_index');
		}

		/**
		 * Handles saving the settings correctly.
		 *
		 */
		public function postConfigSettings(TBGRequest $request)
		{
			$settings = array('ssi_location', 'login_groups', 'admin_groups', 'enabled_groups', 'access_groups', 'password_salt');
			foreach ($settings as $setting)
			{
				if ($request->hasParameter($setting))
				{
					if (in_array($setting, array('login_groups', 'enabled_groups', 'access_groups', 'admin_groups')))
						$value = serialize($request->getParameter($setting));
					else
						$value = $request->getParameter($setting);

					$this->saveSetting($setting, $value);
				}
			}
		}

		/**
		 * Attempts to connect to SMF.
		 * @note: This could in the future detect SMF offline and disable integration.
		 *
		 */
		public function connect()
		{
			$ssi_loc = $this->getSetting('ssi_location');

			// Ignore errors about SMF trying to be pre-PHP 5.3 compatible.
			try
			{
				require_once($ssi_loc . '/SSI.php');
			}
			catch (Exception $e)
			{
				// This ends SMF from trying to do url rewriting and fixes a loading issue in TBG.
				ob_end_clean();
			}

			return true;
		}
		
		public function bind()
		{
		}
		
		public function escape($string)
		{
		}

		/**
		 * Does the actual login by checking you have a valid SMF session.
		 *
		 */
		public function doLogin($username, $password, $mode = 1)
		{
			global $user_info;

			$ssi_loc = $this->getSetting('ssi_location');

			// Ignore errors about SMF trying to be pre-PHP 5.3 compatible.
			try
			{
				require_once($ssi_loc . '/SSI.php');
			}
			catch (Exception $e)
			{
				// This ends SMF from trying to do url rewriting and fixes a loading issue in TBG.
				ob_end_clean();

				// Because of the exception we caught from a deprecated warning, we have to call this manually.
				loadUserSettings();
			}

			$logingroups = unserialize($this->getSetting('login_groups'));
			$admingroups = unserialize($this->getSetting('admin_groups'));
			$salt = $this->getSetting('password_salt');
			$accessgroups = unserialize($this->getSetting('access_groups'));

			// Are they even allowed to login?
			if (!empty($logingroups) && !empty($logingroups[0]) && array_intersect($logingroups, $user_info['groups']) == array())
				throw new Exception(TBGContext::getI18n()->__('You are not a member of a group allowed to log in'));

			// Try to get the user.
			$user = TBGUser::getByUsername($user_info['username']);
			if ($user instanceof TBGUser)
			{
				if (time() - $this->getSetting('smf_auth_updated', $user->getID())> 3600)
				{
					$user->setBuddyname($user_info['username']);
					$user->setRealname($user_info['name']);
					$user->setPassword($user->getJoinedDate() . $user_info['username'] . $salt); // update password
					$user->setEmail($user_info['email']); // update email address
					$user->save();
				}
			}
			else
			{
				// Only do this on the initial login.
				if ($mode == 1)
				{						
					// create user
					$user = new TBGUser();
					$user->setUsername($user_info['username']);
					$user->setRealname($user_info['name']);
					$user->setBuddyname($user_info['username']);
					$user->setEmail($user_info['email']);
					$user->setEnabled();
					$user->setActivated();
					$user->setJoined();
					$user->setPassword($user->getJoinedDate() . $user_info['username'] . $salt);
					$user->save();
				}
				else
					throw new Exception('User does not exist in TBG');
			}

			// Lets only do this every once in a while.
			if (time() - $this->getSetting('smf_auth_updated', $user->getID())> 3600)
			{
				// Nobody admins the admins.
				if (!empty($admingroups) && array_intersect($admingroups, $user_info['groups']))
				{
					$group = new TBGGroup();
					$group->setID(1);
					$user->setGroup($group);
				}
				else
				{
					$group = new TBGGroup();
					$group->setID(2);
					$user->setGroup($group);
				}

				// Give them the access they need!
				if (!empty($accessgroups))
				{
					// First, clear out current teams.
					$user->clearTeams();

					// Then add back the ones they should be in.
					foreach ($accessgroups as $smf_id => $tbg_groups)
						if (in_array($smf_id, $user_info['groups']))
							foreach ($tbg_groups as $tbgroup)
							{
								$team = new TBGTeam();
								$team->setID($tbgroup);
								$user->addToTeam($team);
							}
				}

				// Update the time stamp so we don't keep doing this every page load.
				$this->saveSetting('smf_auth_updated', time(), $user->getID());
			}

			// Set the cookies.
			TBGContext::getResponse()->setCookie('tbg3_username', $user_info['username']);
			TBGContext::getResponse()->setCookie('tbg3_password', TBGUser::hashPassword($user->getJoinedDate() . $user_info['username'] . $salt));

			return TBGUsersTable::getTable()->getByUsername($user_info['username']);
		}

		public function verifyLogin($username)
		{
			// Most likely not the best idea to pass mode of 1, but it allows the direct login method to work.
			return $this->doLogin($username, 'a', 1);
		}
	}

