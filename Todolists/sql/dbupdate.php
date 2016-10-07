<#1>
<?php
$fields = array(
	'id' => array(
		'type' => 'integer',
		'length' => 8,
		'notnull' => true
	),
	'is_online' => array(
		'type' => 'integer',
		'length' => 1,
		'notnull' => false
	),
	'are_finished_shown' => array(
		'type' => 'integer',
		'length' => 1,
		'notnull' => false,
		'default' => 0
	),
	'before_startdate_shown' => array(
		'type' => 'integer',
		'length' => 1,
		'notnull' => false,
		'default' => 0
	),
	'collectlist' => array(
		'type' => 'integer',
		'length' => 8,
		'notnull' => false,
		'default' => 0
	),
	'namen' => array(
	'type' => 'text',
	'length'=> 200,
	'notnull' => true
	),
	'get_collect' => array(
		'type' => 'text',
		'length' => 200,
		'notnull' => false,
	),
	'show_startdate' => array(
		'type' => 'integer',
		'length' => 1,
		'notnull' => false,
		'default' => 0
	),
	'show_createdby' => array(
		'type' => 'integer',
		'length' => 1,
		'notnull' => false,
		'default' => 0
	),
	'show_updatedby' => array(
		'type' => 'integer',
		'length' => 1,
		'notnull' => false,
		'default' => 0
	),
	'show_percent_bar' => array(
		'type' => 'integer',
		'length' => 1,
		'notnull' => false,
		'default' => 0
	),
	'show_edit_status_button' => array(
		'type' => 'integer',
		'length' => 1,
		'notnull' => false,
		'default' => 0
	),
	'edit_status_permission' => array(
	'type' => 'integer',
	'length' => 1,
	'notnull' => false,
	'default' => 0
	),
	'enddate_warning' => array(
		'type' => 'integer',
		'length' => 1,
		'notnull' => false,
		'default' => 1
	),
	'enddate_cursive' => array(
		'type' => 'integer',
		'length' => 1,
		'notnull' => false,
		'default' => 0
	),
	'enddate_fat' => array(
		'type' => 'integer',
		'length' => 1,
		'notnull' => false,
		'default' => 0
	),
	'enddate_color' => array(
		'type' => 'text',
		'length'=> 200,
		'notnull' => true
	)
);

$ilDB->createTable("rep_robj_xtdo_data", $fields);
$ilDB->addPrimaryKey("rep_robj_xtdo_data", array("id"));
?>
<#2>
<?php
	$field = array(
		'objectid' => array(
			'type'     => 'integer',
			'length'   => 8
		),
		'tasks' => array(
			'type' => 'text',
			'length'=> 200,
			'notnull' => true
		),
		'startdate' => array(
			'type' => 'integer',
			'length' => 8,
			'default' => 0
		),
		'enddate' => array(
			'type' => 'integer',
			'length' => 8,
			'default' => 0
		),
		'description' => array(
			'type' => 'text',
			'length'=> 200,
			'notnull' => false
		),
		'edit_status' => array(
			'type' => 'integer',
			'length' => 1,
			'notnull' => false,
			'default' => 0

		),
		'created_by' => array(
			'type' => 'text',
			'length'=> 200,
			'notnull' => false
		),
		'updated_by' => array(
			'type' => 'text',
			'length'=> 200,
			'notnull' => false
		)

	);
	$Sql_string="ALTER TABLE rep_robj_xtdo_tasks ADD id INT NOT NULL AUTO_INCREMENT PRIMARY KEY ";
	$ilDB->createTable("rep_robj_xtdo_tasks", $field);
	$ilDB->query($Sql_string);
?>
<#3>
<?php
	$Sql_string="ALTER TABLE rep_robj_xtdo_data ADD path TEXT ";
	$ilDB->query($Sql_string);
?>
<#4>
<?php
	$field = array(
		'objectid' => array(
			'type'     => 'integer',
			'length'   => 8
		),
		'milestone' => array(
			'type' => 'text',
			'length'=> 200,
			'notnull' => true
		),
		'description' => array(
			'type' => 'text',
			'length'=> 200,
			'notnull' => false
		),
		'progress' => array(
			'type' => 'integer',
			'length' => 1,
			'notnull' => false,
			'default' => 0
	
		),
		'created_by' => array(
			'type' => 'text',
			'length'=> 200,
			'notnull' => false
		),
		'updated_by' => array(
			'type' => 'text',
			'length'=> 200,
			'notnull' => false
		)
	
	);
	$Sql_string="ALTER TABLE rep_robj_xtdo_milsto ADD id INT NOT NULL AUTO_INCREMENT PRIMARY KEY ";
	$ilDB->createTable("rep_robj_xtdo_milsto", $field);
	$ilDB->query($Sql_string);
    
    $Sql_string="ALTER TABLE rep_robj_xtdo_tasks ADD milestone_id INT";
	$ilDB->query($Sql_string);
    
    
    
?>
<#5>
<?php

$Sql_string="ALTER TABLE `rep_robj_xtdo_tasks` CHANGE `enddate` `enddate` DATETIME NULL ";
$ilDB->query($Sql_string);
$Sql_string="ALTER TABLE `rep_robj_xtdo_tasks` CHANGE `startdate` `startdate` DATETIME NULL";
$ilDB->query($Sql_string);
$Sql_string="ALTER TABLE `rep_robj_xtdo_tasks` CHANGE `created_by` `created_by` INT(200) NULL DEFAULT NULL ";
$ilDB->query($Sql_string);
$Sql_string="ALTER TABLE `rep_robj_xtdo_tasks` CHANGE `updated_by` `updated_by` INT(200) NULL DEFAULT NULL";
$ilDB->query($Sql_string);

$Sql_string="ALTER TABLE `rep_robj_xtdo_milsto` CHANGE `created_by` `created_by` INT(200) NULL DEFAULT NULL ";
$ilDB->query($Sql_string);
$Sql_string="ALTER TABLE `rep_robj_xtdo_milsto` CHANGE `updated_by` `updated_by` INT(200) NULL DEFAULT NULL";
$ilDB->query($Sql_string);


?>









