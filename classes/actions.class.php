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
	 * actions for the smf_authentication module
	 */
	class auth_smfActions extends TBGAction
	{

		/**
		 * Test the SMF connection
		 *
		 * @param TBGRequest $request
		 */
		public function runAutoLogin(TBGRequest $request)
		{
			TBGContext::getModule('auth_smf')->verifyLogin($request->getParameter('tbg3_username'));

			$this->forward(TBGContext::getRouting()->generate('home'));
		}
	}
