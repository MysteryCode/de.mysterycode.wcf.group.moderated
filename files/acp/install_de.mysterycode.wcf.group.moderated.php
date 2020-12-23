<?php

use wcf\data\package\PackageCache;
use wcf\data\user\group\MModeratedUserGroup;
use wcf\system\database\table\column\EnumDatabaseTableColumn;
use wcf\system\database\table\column\IntDatabaseTableColumn;
use wcf\system\database\table\column\ObjectIdDatabaseTableColumn;
use wcf\system\database\table\column\TextDatabaseTableColumn;
use wcf\system\database\table\column\TimeDatabaseTableColumn;
use wcf\system\database\table\column\VarcharDatabaseTableColumn;
use wcf\system\database\table\DatabaseTable;
use wcf\system\database\table\DatabaseTableChangeProcessor;
use wcf\system\database\table\index\DatabaseTableForeignKey;
use wcf\system\database\table\index\DatabaseTableIndex;
use wcf\system\database\table\PartialDatabaseTable;
use wcf\system\package\plugin\ScriptPackageInstallationPlugin;
use wcf\system\WCF;

/** @var ScriptPackageInstallationPlugin $this */

$tables = [
	DatabaseTable::create('wcf1_user_group_manager')
		->columns([
			ObjectIdDatabaseTableColumn::create('managerID'),
			IntDatabaseTableColumn::create('userID'),
			IntDatabaseTableColumn::create('groupID'),
		])
		->indices([
			DatabaseTableIndex::create()
				->type(DatabaseTableIndex::UNIQUE_TYPE)
				->columns(['userID', 'groupID']),
		])
		->foreignKeys([
			DatabaseTableForeignKey::create()
				->columns(['userID'])
				->referencedTable('wcf1_user')
				->referencedColumns(['userID'])
				->onDelete('CASCADE'),
			DatabaseTableForeignKey::create()
				->columns(['groupID'])
				->referencedTable('wcf1_user_group')
				->referencedColumns(['groupID'])
				->onDelete('CASCADE'),
		]),
	DatabaseTable::create('wcf1_user_group_request')
		->columns([
			ObjectIdDatabaseTableColumn::create('requestID'),
			IntDatabaseTableColumn::create('userID'),
			VarcharDatabaseTableColumn::create('username')
				->defaultValue('')
				->length(255),
			IntDatabaseTableColumn::create('groupID'),
			IntDatabaseTableColumn::create('comments')
				->defaultValue(0),
			TimeDatabaseTableColumn::create('time')
				->defaultValue(0),
			TimeDatabaseTableColumn::create('changeTime')
				->defaultValue(0),
			TextDatabaseTableColumn::create('message'),
			TextDatabaseTableColumn::create('reply'),
			EnumDatabaseTableColumn::create('status')
				->enumValues([
					'pending',
					'rejected',
					'accepted'
				])
		])
		->indices([
			DatabaseTableIndex::create()
				->type(DatabaseTableIndex::UNIQUE_TYPE)
				->columns(['userID', 'groupID']),
		])
		->foreignKeys([
			DatabaseTableForeignKey::create()
				->columns(['userID'])
				->referencedTable('wcf1_user')
				->referencedColumns(['userID'])
				->onDelete('CASCADE'),
			DatabaseTableForeignKey::create()
				->columns(['groupID'])
				->referencedTable('wcf1_user_group')
				->referencedColumns(['groupID'])
				->onDelete('CASCADE'),
		])
];

(new DatabaseTableChangeProcessor(
/** @var ScriptPackageInstallationPlugin $this */
    $this->installation->getPackage(),
    $tables,
    WCF::getDB()->getEditor())
)->process();

if (PackageCache::getInstance()->getPackageID('com.woltlab.wcf.moderatedUserGroup')) {
	// TODO compatibility
	// copy existing values
}
