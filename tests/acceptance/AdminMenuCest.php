<?php

class AdminMenuCest {

	public function _before( AcceptanceTester $I ) {
	}

	// tests
	public function IsAdminPageOnNetworkTest( AcceptanceTester $I ) {

		$I->loginAsAdmin();
		$I->amOnPage( 'wp-admin/network/' );
		$I->see( 'Content Synccc' );

	}
}
