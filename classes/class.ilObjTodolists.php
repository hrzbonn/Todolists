<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2009 ILIAS open source, University of Cologne            |
	|                                                                             |
	| This program is free software; you can redistribute it and/or               |
	| modify it under the terms of the GNU General Public License                 |
	| as published by the Free Software Foundation; either version 2              |
	| of the License, or (at your option) any later version.                      |
	|                                                                             |
	| This program is distributed in the hope that it will be useful,             |
	| but WITHOUT ANY WARRANTY; without even the implied warranty of              |
	| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
	| GNU General Public License for more details.                                |
	|                                                                             |
	| You should have received a copy of the GNU General Public License           |
	| along with this program; if not, write to the Free Software                 |
	| Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
	+-----------------------------------------------------------------------------+
*/

include_once("./Services/Repository/classes/class.ilObjectPlugin.php");




class ilObjTodolists extends ilObjectPlugin
{
	private $collect_id;

	function __construct($a_ref_id = 0)
	{
		parent::__construct($a_ref_id);
	}
	

	/**
	* Get type.
	*/
	final function initType()
	{
		$this->setType("xtdo");
	}
	
	/**
	* Create object
	*/
	function doCreate()
	{
		global $ilDB;
		
		$ilDB->manipulate("INSERT INTO rep_robj_xtdo_data ".
			"(id, is_online, are_finished_shown, before_startdate_shown , collectlist, namen,show_percent_bar , enddate_color ,get_collect,status_position ) VALUES (".
			$ilDB->quote($this->getId(), "integer").",".
			$ilDB->quote(0, "integer").",".
			$ilDB->quote(0, "integer").",".
			$ilDB->quote(0, "integer").",".
			$ilDB->quote(0, "integer"). ",".
			$ilDB->quote($this->getTitle(), "text"). ",".
			$ilDB->quote(1, "integer"). ",".
			$ilDB->quote("FF0000", "text"). ",".
			$ilDB->quote('', "text").",".
			$ilDB->quote(0, "integer").
			")");
	}
	
	/**
	* Read data from db
	*/
	function doRead()
	{
		global $ilDB;
		
		$set = $ilDB->query("SELECT * FROM rep_robj_xtdo_data ".
			" WHERE id = ".$ilDB->quote($this->getId(), "integer")
			);
		while ($rec = $ilDB->fetchAssoc($set))
		{
			$this->setOnline($rec["is_online"]);
			$this->setAreFinishedShown($rec["are_finished_shown"]);
			$this->setBeforeStartdateShown($rec["before_startdate_shown"]);
			$this->setIsCollectlist($rec["collectlist"]);
			$this->setCollectlistid($rec["get_collect"]);
			$this->setShowHidePercentBarOption($rec["show_percent_bar"]);
			$this->setShowEditStatusButton($rec["show_edit_status_button"]);
			$this->setEditStatusPermission($rec["edit_status_permission"]);
			$this->setShowCreatedBy($rec["show_createdby"]);
			$this->setShowUpdatedBy($rec["show_updatedby"]);
			$this->setShowStartDate($rec["show_startdate"]);
			$this->setEnddateWarning($rec["enddate_warning"]);
			$this->setEnddateCursive($rec["enddate_cursive"]);
			$this->setEnddateFat($rec["enddate_fat"]);
			$this->setEnddateColor($rec["enddate_color"]);
			$this->setStatusPosition($rec["status_position"]);
		}
	}
	
	/**
	* Update data
	*/
	function doUpdate()
	{
		global $ilDB;
		
		$ilDB->manipulate($up = "UPDATE rep_robj_xtdo_data SET ".
			" is_online = ".$ilDB->quote($this->getOnline(), "integer").",".
			" are_finished_shown = ".$ilDB->quote($this->getAreFinishedShown(), "integer").",".
			" before_startdate_shown = ".$ilDB->quote($this->getBeforeStartdateShown(), "integer").",".
			" collectlist = ".$ilDB->quote($this->getIsCollectlist(), "integer").",".
			" show_edit_status_button = ".$ilDB->quote($this->getShowEditStatusButton(), "integer").",".
			" show_percent_bar = ".$ilDB->quote($this->getShowHidePercentBarOption(), "integer").",".
			" namen = ".$ilDB->quote($this->getTitle(), "text").",".
			" edit_status_permission = ".$ilDB->quote($this->getEditStatusPermission(), "integer").",".
			" show_createdby = ".$ilDB->quote($this->getShowCreatedBy(), "integer").",".
			" show_updatedby = ".$ilDB->quote($this->getShowUpdatedBy(), "integer").",".
			" show_startdate = ".$ilDB->quote($this->getShowStartDate(), "integer").",".
			" enddate_warning = ".$ilDB->quote($this->getEnddateWarning(), "integer").",".
			" enddate_cursive = ".$ilDB->quote($this->getEnddateCursive(), "integer").",".
			" enddate_fat = ".$ilDB->quote($this->getEnddateFat(), "integer").",".
			" enddate_color = ".$ilDB->quote($this->getEnddateColor(), "text").",".
			" status_position = ".$ilDB->quote($this->getStatusPosition(), "integer").",".
			" get_collect = ".$ilDB->quote($this->getCollectlistid(), "text").
			" WHERE id = ".$ilDB->quote($this->getId(), "integer")
			);
	}
	
	/**
	* Delete data from db
	 *wenn objekt aus dem System entfernt wurde,
	 * ist der Papierkorb aktiv muss das objekt aus diesem gelöscht werden damit die Funktion aufgerufen wird
	*/
	function doDelete()
	{
		global $ilDB;
		$ilDB->manipulate("DELETE FROM rep_robj_xtdo_data WHERE ".
			" id = ".$ilDB->quote($this->getId(), "integer")
			);
		$ilDB->manipulate("DELETE FROM rep_robj_xtdo_tasks WHERE ".
			" objectid = ".$ilDB->quote($this->getId(), "integer")
		);
		$ilDB->manipulate("DELETE FROM rep_robj_xtdo_milsto WHERE ".
			" objectid = ".$ilDB->quote($this->getId(), "integer")
		);
		
	}
	
	/**
	* Do Cloning
	*/
	function doCloneObject($new_obj, $a_target_id, $a_copy_id = null)
	{
		global $ilDB;
		$new_obj->setOnline($this->getOnline());
		$new_obj->setAreFinishedShown($this->getAreFinishedShown());
		$new_obj->setBeforeStartdateShown($this->getBeforeStartdateShown());
		$new_obj->setIsCollectlist($this->getIsCollectlist());
		$new_obj->setCollectlistid($this->getCollectlistid());
		$new_obj->setShowHidePercentBarOption($this->getShowHidePercentBarOption());
		$new_obj->setShowEditStatusButton($this->getShowEditStatusButton());
		$new_obj->setEditStatusPermission($this->getEditStatusPermission());
		$new_obj->setTasks($this->getId());
		$new_obj->setMilestones($this->getId());
		$new_obj->setEnddateWarning($this->getEnddateWarning());
		$new_obj->setEnddateCursive($this->getEnddateCursive());
		$new_obj->setEnddateFat($this->getEnddateFat());
		$new_obj->setEnddateColor($this->getEnddateColor());
		$new_obj->setShowCreatedBy($this->getShowCreatedBy());
		$new_obj->setShowStartDate($this->getShowStartDate());
		$new_obj->setShowUpdatedBy($this->getShowUpdatedBy());
		$new_obj->setStatusPosition($this->getStatusPosition());
		$new_obj->update();
	}

	
	function setStatusPosition($a_value)
	{
		$this->statusPosition=$a_value;
	}
	function getStatusPosition()
	{
		return $this->statusPosition;
	}
	
	function setMilestones($object_id)
	{
		global $ilDB,$ilUser;

		$sql_string="SELECT * FROM rep_robj_xtdo_milsto WHERE objectid = ". $ilDB->quote($object_id,"integer");
		$result = $ilDB->query($sql_string);

		while ($record = $ilDB->fetchAssoc($result))
		{

			if($record["description"] == NULL)
			{
				$record["description"]='';
			}

			$ilDB->manipulateF("INSERT INTO rep_robj_xtdo_milsto (objectid, milestone, description,progress,created_by,updated_by) VALUES " .
				" (%s,%s,%s,%s,%s,%s)",
				array("integer", "text", "text", "integer", "integer", "integer"),
				array($this->getId(), $record["milestone"], $record["description"], $record["progress"], $record["created_by"], $record["updated_by"])
			);


			$sql_string="SELECT id FROM rep_robj_xtdo_milsto WHERE objectid = ". $ilDB->quote($this->getId(),"integer")." AND milestone = '".$record["milestone"]."'";
			$in_result = $ilDB->query($sql_string);
			while ($in_record = $ilDB->fetchAssoc($in_result))
			{
				$milestone_id =$in_record["id"];
			}

			$sql_string="SELECT * FROM rep_robj_xtdo_tasks WHERE objectid = ". $ilDB->quote($object_id,"integer")." AND milestone_id = ". $ilDB->quote($record["id"],"integer");
			$in_result = $ilDB->query($sql_string);
			while ($in_record = $ilDB->fetchAssoc($in_result))
			{
				if($in_record["description"] == NULL)
				{
					$in_record["description"]='';
				}
				$ilDB->manipulateF("INSERT INTO rep_robj_xtdo_tasks (objectid, tasks, startdate, enddate, description,edit_status,created_by,updated_by,milestone_id) VALUES " .
					" (%s,%s,%s,%s,%s,%s,%s,%s,%s)",
					array("integer", "text", "text", "text", "text", "integer","text","text","integer"),
					array($this->getId(), $in_record["tasks"], $in_record["startdate"], $in_record["enddate"], $in_record["description"], $in_record["edit_status"],$in_record["created_by"],$in_record["updated_by"],$milestone_id)
				);
			}




		}





	}


	
	function setEnddateWarning($a_val)
	{
		$this->enddate_warning=$a_val;
	}
	function getEnddateWarning()
	{
		return $this->enddate_warning;
	}

	function setEnddateCursive($a_val)
	{
		$this->enddate_cursive=$a_val;
	}
	function getEnddateCursive()
	{
		return $this->enddate_cursive;
	}

	function setEnddateFat($a_val)
	{
		$this->enddate_fat=$a_val;
	}
	function getEnddateFat()
	{
		return $this->enddate_fat;
	}

	function setEnddateColor($a_val)
	{
		$this->enddate_color=$a_val;
	}
	function getEnddateColor()
	{
		return $this->enddate_color;
	}
	
	function setOnline($a_val)
	{
		$this->online = $a_val;
	}
	
	function getOnline()
	{
		return $this->online;
	}
	
	function setAreFinishedShown($a_val)
	{
		$this->are_finished_shown = $a_val;
	}
	
	function getAreFinishedShown()
	{
		return $this->are_finished_shown;
	}
	
	function setBeforeStartdateShown($a_val)
	{
		$this->option_two = $a_val;
	}
	
	function getBeforeStartdateShown()
	{
		return $this->option_two;
	}
	
	function setIsCollectlist($a_val)
	{
		$this->option_three = $a_val;
	}
	function getIsCollectlist()
	{
		return $this->option_three;
	}

	function setShowHidePercentBarOption($a_val)
	{
		$this->showHidePercentBarOption=$a_val;
	}

	function getShowHidePercentBarOption()
	{
		return $this->showHidePercentBarOption;
	}

	function setShowEditStatusButton($a_val)
	{
		$this->show_edit_status_button=$a_val;
	}

	function getShowEditStatusButton()
	{
		return $this->show_edit_status_button;
	}
	function setEditStatusPermission($a_val)
	{
		$this->edit_status_permission=$a_val;
	}
	function getEditStatusPermission()
	{
		return $this->edit_status_permission;
	}


	function setCollectlistid($a_val)
	{
		$this->collect_id=$a_val;
	}
	function getCollectlistid()
	{
		return $this->collect_id;
	}

	function setShowCreatedBy($a_val)
	{
		$this->show_created_by=$a_val;
	}
	function getShowCreatedBy()
	{
		return $this->show_created_by;
	}

	function setShowUpdatedBy($a_val)
	{
		$this->show_updated_by=$a_val;
	}
	function getShowUpdatedBy()
	{
		return $this->show_updated_by;
	}

	function setShowStartDate($a_val)
	{
		$this->show_start_date=$a_val;
	}
	function getShowStartDate()
	{
		return $this->show_start_date;
	}
	
	function setTasks($id)
	{
		global $ilDB,$ilUser;

		$sql_string="SELECT * FROM rep_robj_xtdo_tasks WHERE objectid = ". $ilDB->quote($id,"integer");
		$result = $ilDB->query($sql_string);

		while ($record = $ilDB->fetchAssoc($result))
		{
			if($record["description"] == NULL)
			{
				$record["description"]='';
			}
			if($record["updated_by"] == NULL)
			{
				$record["updated_by"]='';
			}
			if($record["milestone_id"] == 0)
			$ilDB->manipulateF("INSERT INTO rep_robj_xtdo_tasks (objectid, tasks, startdate, enddate, description,edit_status,created_by,updated_by,milestone_id) VALUES " .
				" (%s,%s,%s,%s,%s,%s,%s,%s,%s)",
				array("integer", "text", "text", "text", "text", "integer","text","text","integer"),
				array($this->getId(), $record["tasks"], $record["startdate"], $record["enddate"], $record["description"], $record["edit_status"],$record["created_by"],$record["updated_by"],0)
			);

		}

	}

	
}
?>
