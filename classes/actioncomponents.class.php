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
	 * action components for the smf_authentication module
	 */
	class auth_smfActionComponents extends TBGActionComponent
	{
		public function componentSettings()
		{
			global $smcFunc;

			$ssi_loc = TBGContext::getModule('auth_smf')->getSetting('ssi_location');

			// New setups we default the group and create a random hash.
			if (empty($ssi_loc))
			{
				$this->smf_groups = array();
				$this->new_salt = substr(sha1(mt_rand() . time()), rand(0, 3), rand(7, 9));

				return;
			}

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

 			if (!isset($smcFunc, $smcFunc['db_query']))
			{
				$this->smf_groups = array();
				return;
			}

			// Load up the SMF groups.
			$request = $smcFunc['db_query']('', '
				SELECT id_group, group_name
				FROM {db_prefix}membergroups
				WHERE min_posts = {int:min_posts}',
				array(
					'min_posts' => -1,
			));

			$smf_groups = array();
			while ($row = $smcFunc['db_fetch_assoc']($request))
				$smf_groups[$row['id_group']] = $row['group_name'];
			$smcFunc['db_free_result']($request);

			$this->smf_groups = $smf_groups;
		}
	}

