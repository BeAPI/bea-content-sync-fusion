<?php

class AdminMenuCest {

	public function _before( AcceptanceTester $I ) {
	}

	// tests
	public function IsAdminPageOnNetworkTest( AcceptanceTester $I ) {

		$I->loginAsAdmin();

		$I->wantToTest( 'Content Sync is on admin network menu' );
		$I->amOnPage( '/wp-admin/network/' );
		$I->see( 'Content Sync' );

		$I->wantToTest( 'Content Sync is on admin network menu, with submenu' );
		$I->amOnPage( '/wp-admin/network/admin.php?page=bea-csf-edit' );
		$I->see( 'Edit' );
		$I->see( 'Add' );
		$I->see( 'Queue' );
		$I->see( 'No synchronization exists.' );
		$I->see( 'Content Sync: Advanced settings' );
		$I->see( 'Special mode' );
	}

	public function IsAdminAddWorkingEmpty( AcceptanceTester $I ) {
		$I->loginAsAdmin();

		$I->wantToTest( 'Synchronization add' );
		$I->amOnPage( '/wp-admin/network/admin.php?page=bea-csf-add' );

		// Empty form
		$I->click( 'input[name=update-bea-csf-settings]' );
		$I->see( 'You must defined a label.' );
	}

	public function IsAdminAddWorkingMissingEmitter( AcceptanceTester $I ) {
		$I->loginAsAdmin();

		$I->wantToTest( 'Missing emitter on Add' );
		$I->amOnPage( '/wp-admin/network/admin.php?page=bea-csf-add' );

		// Missing emitter
		$I->fillField( [ 'name' => 'sync[label]' ], 'TEST' );
		$I->selectOption( 'sync[post_type]', 'post' );
		$I->click( '#bea-csf-taxonomies-block ul li.ms-elem-selectable:first-of-type' );
		$I->selectOption( 'sync[mode]', 'auto' );
		$I->selectOption( 'sync[status]', 'publish' );
		$I->click( '#bea-csf-receivers-block ul li.ms-elem-selectable:first-of-type' );
		$I->click( 'input[name=update-bea-csf-settings]' );
		$I->see( 'You must defined at least one emitter.' );
	}

	public function IsAdminAddWorkingMissingReceiver( AcceptanceTester $I ) {
		$I->loginAsAdmin();

		$I->wantToTest( 'Missing receiver on Add' );
		$I->amOnPage( '/wp-admin/network/admin.php?page=bea-csf-add' );

		// Missing receiver
		$I->fillField( [ 'name' => 'sync[label]' ], 'TEST' );
		$I->selectOption( 'sync[post_type]', 'post' );
		$I->click( '#bea-csf-taxonomies-block ul li.ms-elem-selectable:first-of-type' );
		$I->selectOption( 'sync[mode]', 'auto' );
		$I->selectOption( 'sync[status]', 'publish' );
		$I->click( '#bea-csf-emitters-block ul li.ms-elem-selectable:first-of-type' );
		$I->click( 'input[name=update-bea-csf-settings]' );
		$I->see( 'You must defined at least one receiver.' );
	}

	public function IsAdminAddWorkingMissingLabel( AcceptanceTester $I ) {
		$I->loginAsAdmin();

		$I->wantToTest( 'Missing Label on ADD' );
		$I->amOnPage( '/wp-admin/network/admin.php?page=bea-csf-add' );

		// Missing label
		$I->selectOption( 'sync[post_type]', 'post' );
		$I->click( '#bea-csf-taxonomies-block ul li.ms-elem-selectable:first-of-type' );
		$I->selectOption( 'sync[mode]', 'auto' );
		$I->selectOption( 'sync[status]', 'publish' );
		$I->click( '#bea-csf-emitters-block ul li.ms-elem-selectable:first-of-type' );
		$I->click( '#bea-csf-receivers-block ul li.ms-elem-selectable:first-of-type' );
		$I->click( 'input[name=update-bea-csf-settings]' );

		$I->see( 'You must defined a label.' );
	}

	public function IsAdminAddWorkingWorking( AcceptanceTester $I ) {
		$I->loginAsAdmin();

		$I->wantToTest( 'Adding is working' );
		$I->amOnPage( '/wp-admin/network/admin.php?page=bea-csf-add' );

		// Missing label
		$I->fillField( [ 'name' => 'sync[label]' ], 'TEST' );
		$I->selectOption( 'sync[post_type]', 'post' );
		$I->click( '#bea-csf-taxonomies-block ul li.ms-elem-selectable:first-of-type' );
		$I->selectOption( 'sync[mode]', 'auto' );
		$I->selectOption( 'sync[status]', 'publish' );
		$I->click( '#bea-csf-emitters-block ul li.ms-elem-selectable:first-of-type' );
		$I->click( '#bea-csf-receivers-block ul li.ms-elem-selectable:first-of-type' );
		$I->click( 'input[name=update-bea-csf-settings]' );

		$I->dontSee( 'No synchronization exists.' );
	}

	/**
	 * TODO: function description
	 *
	 * @param AcceptanceTester $I
	 * @before IsAdminAddWorkingWorking
	 *
	 * @author Nicolas JUEN
	 */
	public function IsAdminRemoveWorking( AcceptanceTester $I ) {
		$I->loginAsAdmin();

		$I->wantToTest( 'Removing relation' );
		$I->amOnPage( '/wp-admin/network/admin.php?page=bea-csf-edit' );

		// Missing label
		$I->click( '.row-actions .delete' );
		$I->seeInPopup( "You are about to delete this sync 'Test' \n 'Cancel' to stop, 'OK' to delete." );
		$I->acceptPopup();

		$I->see( 'No synchronization exists.' );
	}
}
