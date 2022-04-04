<?php

use wcf\system\database\table\column\DefaultFalseBooleanDatabaseTableColumn;
use wcf\system\database\table\column\EnumDatabaseTableColumn;
use wcf\system\database\table\column\IntDatabaseTableColumn;
use wcf\system\database\table\column\NotNullInt10DatabaseTableColumn;
use wcf\system\database\table\column\ObjectIdDatabaseTableColumn;
use wcf\system\database\table\column\TextDatabaseTableColumn;
use wcf\system\database\table\column\VarcharDatabaseTableColumn;
use wcf\system\database\table\DatabaseTable;
use wcf\system\database\table\index\DatabaseTableForeignKey;
use wcf\system\database\table\index\DatabaseTableIndex;

return [
	DatabaseTable::create('wcf1_user_group_manager')
		->columns([
			ObjectIdDatabaseTableColumn::create('managerID'),
			IntDatabaseTableColumn::create('userID')
				->length(10),
			IntDatabaseTableColumn::create('groupID')
				->length(10),
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
			IntDatabaseTableColumn::create('userID')
				->length(10),
			VarcharDatabaseTableColumn::create('username')
				->defaultValue('')
				->length(255),
			IntDatabaseTableColumn::create('groupID')
				->length(10),
			NotNullInt10DatabaseTableColumn::create('comments')
				->defaultValue(0),
			NotNullInt10DatabaseTableColumn::create('time')
				->defaultValue(0),
			NotNullInt10DatabaseTableColumn::create('changeTime')
				->defaultValue(0),
			TextDatabaseTableColumn::create('message'),
			DefaultFalseBooleanDatabaseTableColumn::create('messageHasEmbeddedObjects'),
			TextDatabaseTableColumn::create('reply'),
			DefaultFalseBooleanDatabaseTableColumn::create('replyHasEmbeddedObjects'),
			EnumDatabaseTableColumn::create('status')
				->enumValues([
					'pending',
					'rejected',
					'accepted',
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
		]),
];
