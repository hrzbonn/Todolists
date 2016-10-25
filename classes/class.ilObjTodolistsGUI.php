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


include_once("./Services/Repository/classes/class.ilObjectPluginGUI.php");
include_once ("Services/Utilities/classes/class.ilConfirmationGUI.php");

/**
* User Interface class for example repository object.
*
* User interface classes process GET and POST parameter and call
* application classes to fulfill certain tasks.
*
* @author Alex Killing <alex.killing@gmx.de>
*
* $Id$
*
* Integration into control structure:
* - The GUI class is called by ilRepositoryGUI
* - GUI classes used by this class are ilPermissionGUI (provides the rbac
*   screens) and ilInfoScreenGUI (handles the info screen).
*
* @ilCtrl_isCalledBy ilObjTodolistsGUI: ilRepositoryGUI, ilAdministrationGUI, ilObjPluginDispatchGUI, ilMyTableGUI,ilTaskListGUI,ilMilestoneListGUI,ilMilestoneTaskListGUI
* @ilCtrl_Calls ilObjTodolistsGUI: ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI, ilCommonActionDispatcherGUI, ilTaskListGUI,ilMilestoneListGUI,ilMilestoneTaskListGUI
*
*/
class ilObjTodolistsGUI extends ilObjectPluginGUI
{

	private $all;
	private $myRefId;
	/**
	* Initialisation
	*/
	protected function afterConstructor()
	{

		if(isset($_GET['open']))
		{
			if($_GET['open']==1)
			{
				$this->all=true;
			}else
			{
				$this->all=false;
			}
		}else
		{
			$this->all=false;
		}
		// anything needed after object has been constructed
		// - example: append my_id GET parameter to each request
		//   $ilCtrl->saveParameter($this, array("my_id"));

	}

	/**
	* Get type.
	*/
	final function getType()
	{
		return "xtdo";
	}
	
	/**
	* Handles all commmands of this class, centralizes permission checks
	*/
	function performCommand($cmd)
	{
		switch ($cmd)
		{
			case "firststart":
			case "updatefirststart":
			case "editProperties":		// list all commands that need write permission here
			case "updateProperties":
			case "milestone":
			case "addmilestoneall":
			case "newmilestone":
			case "resetMilestoneListFilter":
			case "applyMilestoneListFilter":
			case "edit_row_milestone":
			case "delete_row_milestone":
			case "cancel_edit_milestone":
			case "save_edit_milestone":
			case "deletemilestone":
				$this->checkPermission("write");
				$this->$cmd();
				break;
			
			case "applyFilter":
			case "resetFilter":
			case "applyMilestoneFilter":
			case "resetMilestoneFilter":
			case "newtask":
			case "showContent":			// list all commands that need read permission here
			case "moreProperties":
			case "lessProperties":
			case "deletetask":
			case "delete_row":
			case "cancelmyDelete":
			case "edit_row":
		    case "cancel_edit":
			case "save_edit":
			case "changestatus":
			case "addtaskall":
			case "addtoalllist":
			case "first_plugin_start":
				$this->checkPermission("read");
				$this->$cmd();
				break;
		}
	}

	/**
	* After object has been created -> jump to this command
	*/
	function getAfterCreationCmd()
	{
		return "firststart";
	}

	/**
	* Get standard command
	*/
	function getStandardCmd()
	{
		return "first_plugin_start";
	}

	function first_plugin_start()
	{
		global $ilCtrl;
		$ilCtrl->redirect($this, "showContent");
	}


//----------------------------------------------------------------------------------------------------------------------

	function changestatus()
	{
		global $lng,$ilCtrl,$ilDB,$ilUser;
		$user=$ilUser->getid();
		if(isset($_REQUEST['id']))
		{
			$sql_string="SELECT edit_status FROM rep_robj_xtdo_tasks WHERE id = ". $ilDB->quote($_REQUEST['id'],"integer");
			$result = $ilDB->query($sql_string);
			while ($record = $ilDB->fetchAssoc($result))
			{
				$status=$record["edit_status"];
			}
			if($status==$_REQUEST['status'])
			{
				$ilDB->query("UPDATE rep_robj_xtdo_tasks SET edit_status= " . $ilDB->quote(!$status, "integer") . " WHERE id = " . $ilDB->quote($_REQUEST['id'], "integer"));
				$ilDB->query("UPDATE rep_robj_xtdo_tasks SET updated_by= " . $ilDB->quote($user, "integer") . " WHERE id = " . $ilDB->quote($_REQUEST['id'], "integer"));
				ilUtil::sendSuccess($lng->txt("msg_obj_modified"), true);
			}else
			{
				ilUtil::sendFailure($this->txt('doublesubmission'),true);
			}

		}
		$ilCtrl->redirect($this, "showContent");
	}

//----------------------------------------------------------------------------------------------------------------------



	function getPath()
	{
		global $ilLocator;
		$items = $ilLocator->getItems();
		$pfad="";
		$count=0;
		if (is_array($items))
		{
			foreach($items as $item)
			{
				if($count>=(count($items)-3) AND $count<=(count($items)-2) ) {
					$pfad .= $item["title"];
					$pfad .= '->';
				}
				$count++;
			}
		}
		return substr($pfad,0,strlen($pfad)-2);
	}

	function firststart()
	{
		global $tpl, $ilTabs;

		global $ilDB;

		$ilDB->manipulate("UPDATE rep_robj_xtdo_data SET path = ".$ilDB->quote($this->getPath(), "text") . " WHERE id = " . $ilDB->quote($this->object_id, "integer"));


		$ilTabs->activateTab("properties");
		$this->initfirststart();
		$this->getfirststartValues();
		$tpl->setContent($this->form->getHTML());
	}
	
	public function initfirststart()
	{
		global $ilCtrl;

		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
		$this->form = new ilPropertyFormGUI();

		// title
		$ti = new ilTextInputGUI($this->txt("title"), "title");
		$ti->setRequired(true);
		$this->form->addItem($ti);

		// description
		$ta = new ilTextAreaInputGUI($this->txt("description"), "desc");
		$this->form->addItem($ta);


		$ti = new ilCheckboxInputGUI($this->txt("is_collectlist"), "is_collectlist");
		$ti->setInfo($this->txt('is_collectlist_info'));
		$this->form->addItem($ti);

		$this->form->addCommandButton("updatefirststart", $this->txt("save"));

		$this->form->setTitle($this->txt("first_start"));
		$this->form->setFormAction($ilCtrl->getFormAction($this));
	}
	
	function getfirststartValues()
	{
		$values["title"] = $this->object->getTitle();
		$values["desc"] = $this->object->getDescription();
		$values["is_collectlist"] = $this->object->getIsCollectlist();
		$this->form->setValuesByArray($values);
	}

	public function updatefirststart()
	{
		global $tpl, $lng, $ilCtrl;

		$this->initPropertiesForm();
		if ($this->form->checkInput())
		{
			$this->object->setTitle($this->form->getInput("title"));
			$this->object->setDescription($this->form->getInput("desc"));
			$this->object->setIsCollectlist($this->form->getInput("is_collectlist"));
			$this->object->update();
			ilUtil::sendSuccess($lng->txt("msg_obj_modified"), true);
			$ilCtrl->redirect($this, "showContent");
		}

		$this->form->setValuesByPost();
		$tpl->setContent($this->form->getHtml());
	}


//--------------------------------------------------------------------------------------------------------------------------------
	function edit_row_milestone()
	{
		global $tpl,$ilCtrl,$ilTabs;
		if(isset($_GET['data_id']))
		{
			$id =$_GET['data_id'];
		}
		$ilCtrl->setParameterByClass("ilObjTodolistsGUI", "data_id",$id);
		$ilTabs->activateTab("milestone");
		$this->initEditRowMilestoneForm();
		$this->getEditRowMilestoneValues($id);
		$tpl->setContent($this->form->getHTML());
	}

	function initEditRowMilestoneForm()
	{
		global $ilCtrl,$lng;

		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
		$this->form = new ilPropertyFormGUI();

		$ti = new ilTextInputGUI($this->txt("todo"), "todo");
		$this->form->addItem($ti);
		$ti->setRequired(true);

		$ti = new ilTextAreaInputGUI($this->txt("description"), "description");
		$this->form->addItem($ti);

		$this->form->addCommandButton("save_edit_milestone", $lng->txt('save'));
		$this->form->addCommandButton("cancel_edit_milestone", $lng->txt("cancel"));


		$this->form->setTitle($this->txt("edit_task"));
		$this->form->setFormAction($ilCtrl->getFormAction($this));
	}
	function getEditRowMilestoneValues($id)
	{
		global $ilDB;
		$result = $ilDB->query("SELECT milestone,description FROM rep_robj_xtdo_milsto WHERE id = ". $ilDB->quote($id,"integer"));
		while ($record = $ilDB->fetchAssoc($result))
		{
			$values['todo']=$record['milestone'];
			$values['description']=$record['description'];
		}
		$this->form->setValuesByArray($values);
	}
	function save_edit_milestone()
	{
		$this->updateEditRowMilestone($this->getEditRowMilestoneFromForm());
		$this->milestone();
	}


	function getEditRowMilestoneFromForm()
	{
		global $lng;
		$this->initEditRowMilestoneForm();
		if ($this->form->checkInput())
		{
				$values['milestone']=$this->form->getInput("todo");
				$values['description'] = $this->form->getInput("description");
				ilUtil::sendSuccess($lng->txt("msg_obj_modified"), true);
				return $values;
		}

	}

	function updateEditRowMilestone($values)
	{
		global $ilDB;
		global $ilUser;
		$user = $ilUser->getid();
		if(!empty($values)) {

			if(isset($_REQUEST['data_id']))
			{
				$id =$_REQUEST['data_id'];
			}
			if(isset($values['description']))
			{
				$ilDB->query("UPDATE rep_robj_xtdo_milsto SET description= ".$ilDB->quote($values['description'], "text")." WHERE id = ".$ilDB->quote($id, "integer"));
			}
			if(isset($values['milestone']))
			{
				$ilDB->query("UPDATE rep_robj_xtdo_milsto SET milestone= ".$ilDB->quote($values['milestone'], "text")." WHERE id = ".$ilDB->quote($id, "integer"));
			}
			$ilDB->query("UPDATE rep_robj_xtdo_milsto SET updated_by = ".$ilDB->quote($user, "integer")." WHERE id = ".$ilDB->quote($id, "integer"));
		}
	}




	function cancel_edit_milestone()
	{
		$this->milestone();
	}

	function cancel_edit()
	{
		$this->showContent();
	}

	function edit_row()
	{
		global $tpl,$ilCtrl,$ilTabs;
		if(isset($_GET['data_id']))
		{
			$id =$_GET['data_id'];
		}
		$ilCtrl->setParameterByClass("ilObjTodolistsGUI", "data_id",$id);
		$ilTabs->activateTab("content");
		$this->initEditRowForm($id);
		$this->getEditRowValues($id);
		$tpl->setContent($this->form->getHTML());
	}

	function save_edit()
	{
		$this->updateEditRow($this->getEditRowFromForm());
		$this->showContent();
	}

	function initEditRowForm($id)
	{
		global $ilCtrl,$lng,$ilSetting;

		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
		$this->form = new ilPropertyFormGUI();

		$ti = new ilTextInputGUI($this->txt("todo"), "todo");
		$this->form->addItem($ti);
		$ti->setRequired(true);

		$optionen=array($this->txt("nothing"));
		$optionen=$this->getMileStonesNamesEditRow($optionen,$id);
		$ti = new ilSelectInputGUI($this->txt("milestone_task"),"milestone_task");
		$ti->setOptions($optionen);
		$this->form->addItem($ti);

		$ti = new ilDateTimeInputGUI($this->txt("startdate"),'startdate');
		if($this->versionBigger52()) $ti->setMode(ilDateTimeInputGUI::MODE_INPUT);
		$this->form->addItem($ti);
		$ti = new ilDateTimeInputGUI($this->txt("enddate"),'enddate');
		if($this->versionBigger52()) $ti->setMode(ilDateTimeInputGUI::MODE_INPUT);
		$this->form->addItem($ti);

		$ti = new ilTextAreaInputGUI($this->txt("description"), "description");
		$this->form->addItem($ti);

		$this->form->addCommandButton("save_edit", $lng->txt('save'));
		$this->form->addCommandButton("cancel_edit", $lng->txt("cancel"));


		$this->form->setTitle($this->txt("edit_task"));
		$this->form->setFormAction($ilCtrl->getFormAction($this));
	}

	public function getMileStonesNamesEditRow($array=array(),$id)
	{
		global $ilDB;

		$sql_string="SELECT objectid FROM rep_robj_xtdo_tasks WHERE ";
		$sql_string=$sql_string."id = ".$id;

		$result = $ilDB->query($sql_string);
		while ($record = $ilDB->fetchAssoc($result))
		{
			$objectid=$record["objectid"];
		}



		$sql_string="SELECT milestone FROM rep_robj_xtdo_milsto WHERE ";
		$sql_string=$sql_string."objectid = ".$objectid;

		$result = $ilDB->query($sql_string);
		while ($record = $ilDB->fetchAssoc($result))
		{
			array_push($array,$record["milestone"]);
		}
		return $array;
	}


	function getEditRowValues($id)
	{
		global $ilDB,$ilSetting;
		$result = $ilDB->query("SELECT tasks,startdate,enddate,description,milestone_id FROM rep_robj_xtdo_tasks WHERE id = ". $ilDB->quote($id,"integer"));
		while ($record = $ilDB->fetchAssoc($result))
		{

			$result_milestone = $ilDB->query("SELECT milestone FROM rep_robj_xtdo_milsto WHERE id = ". $ilDB->quote($record['milestone_id'],"integer"));
			while ($milestone_record =$ilDB->fetchAssoc($result_milestone))
			{
				$name = $milestone_record["milestone"];
			}
			$optionen =$this->getMileStonesNamesEditRow(array("keine"),$id);
			$id_in_array=array_search($name,$optionen);

			$values['todo']=$record['tasks'];
			if($record['startdate'] AND $this->versionBigger52())
			{
				$startdate["date"] = date("d.m.Y", strtotime($record['startdate']));
			}
			if($record['enddate'] AND $this->versionBigger52())
			{
				$enddate["date"]=date("d.m.Y",strtotime($record['enddate']));
			}

			if($record['startdate'] AND $this->versionBigger52())
			{
				$startdate = date("d.m.Y", strtotime($record['startdate']));
			}
			if($record['enddate'] AND $this->versionBigger52())
			{
				$enddate=date("d.m.Y",strtotime($record['enddate']));
			}



			$values['description']=$record['description'];
			$values['milestone_task']=$id_in_array;
		}
		$values['startdate']=$startdate;
		$values['enddate']=$enddate;
		$this->form->setValuesByArray($values);

	}

	function getEditRowFromForm()
	{
		global $lng,$ilCtrl,$ilSetting;
		$this->inittodosForm();

		if ($this->form->checkInput())
		{

			if($this->versionBigger52())
			{
				$startdate_array = $this->form->getInput("startdate");
				$enddate_array = $this->form->getInput("enddate");

				if (strtotime($startdate_array['date']) <= strtotime($enddate_array['date']) OR $enddate_array['date'] == "") {
					$values['task']=$this->form->getInput("todo");
					$values['startdate'] = $startdate_array['date'];
					$values['enddate'] = $enddate_array['date'];
					$values['description'] = $this->form->getInput("description");
					$values['milestone_task'] = $this->form->getInput("milestone_task");
					ilUtil::sendSuccess($lng->txt("msg_obj_modified"), true);
				} else {
					ilUtil::sendFailure($lng->txt("form_input_not_valid"),true);
					$ilCtrl->setParameterByClass("ilObjTodolistsGUI", "data_id",$_GET['data_id']);
					$ilCtrl->redirect($this, "edit_row");
				}
			}else
			{
				$startdate_array = $this->form->getInput("startdate");
				$enddate_array = $this->form->getInput("enddate");
				if (strtotime($startdate_array) <= strtotime($enddate_array) OR $enddate_array == "") {
					$values['task'] = $this->form->getInput("todo");
					$values['milestone_task'] = $this->form->getInput("milestone_task");
					$values['startdate'] = $startdate_array;
					$values['enddate'] = $enddate_array;
					$values['description'] = $this->form->getInput("description");
					ilUtil::sendSuccess($lng->txt("msg_obj_modified"), true);
				} else {
					ilUtil::sendFailure($lng->txt("form_input_not_valid"),true);
					$ilCtrl->setParameterByClass("ilObjTodolistsGUI", "data_id",$_GET['data_id']);
					$ilCtrl->redirect($this, "edit_row");
				}
			}
			return $values;
		}else
		{
			ilUtil::sendFailure($lng->txt("form_input_not_valid"),true);
			$ilCtrl->setParameterByClass("ilObjTodolistsGUI", "data_id",$_GET['data_id']);
			$ilCtrl->redirect($this, "edit_row");
		}

	}

	function updateEditRow($values)
	{
		global $ilDB;
		global $ilUser;
		if(!empty($values)) {

			if(isset($_REQUEST['data_id']))
			{
				$id =$_REQUEST['data_id'];
			}
			if(isset($values['milestone_task']))
			{

				$optionen=array($this->txt("nothing"));
				$optionen=$this->getMileStonesNamesEditRow($optionen,$id);

				$option=$values["milestone_task"];
				if($values["milestone_task"] == "" OR $values["milestone_task"] == NULL)
				{
					$option=0;
				}

				global $ilDB;
				$sql_string="SELECT objectid FROM rep_robj_xtdo_tasks WHERE id = ". $ilDB->quote($id,"integer");
				$result = $ilDB->query($sql_string);
				while ($record = $ilDB->fetchAssoc($result))
				{
					$objectid=$record['objectid'];
				}



				$milestone_id=$this->getMilestoneIdWithNameAndOtherObjectId($optionen[$option],$objectid);
				$ilDB->query("UPDATE rep_robj_xtdo_tasks SET milestone_id= ".$ilDB->quote($milestone_id, "integer")." WHERE id = ".$ilDB->quote($id, "integer"));
			}
			if(isset($values['startdate']))
			{
				if($values['startdate'] != NULL) {
					$date = date("Y-m-d", strtotime(str_replace('.', '-', $values['startdate'])));
					$ilDB->query("UPDATE rep_robj_xtdo_tasks SET startdate= " . $ilDB->quote($date, "text") . " WHERE id = " . $ilDB->quote($id, "integer"));
				}
				else
				{
					$ilDB->query("UPDATE rep_robj_xtdo_tasks SET startdate = NULL " . " WHERE id = " . $ilDB->quote($id, "integer"));
				}
			}
			if(isset($values['enddate']))
			{
				if($values['enddate'] != NULL)
				{
					$date = date("Y-m-d", strtotime(str_replace('.', '-', $values['enddate'])));
					$ilDB->query("UPDATE rep_robj_xtdo_tasks SET enddate= " . $ilDB->quote($date, "text") . " WHERE id = " . $ilDB->quote($id, "integer"));
				}else
				{
					$ilDB->query("UPDATE rep_robj_xtdo_tasks SET enddate= NULL " . " WHERE id = " . $ilDB->quote($id, "integer"));
				}
			}
			if(isset($values['description']))
			{
				$ilDB->query("UPDATE rep_robj_xtdo_tasks SET description= ".$ilDB->quote($values['description'], "text")." WHERE id = ".$ilDB->quote($id, "integer"));
			}
			if(isset($values['task']))
			{
				$ilDB->query("UPDATE rep_robj_xtdo_tasks SET tasks= ".$ilDB->quote($values['task'], "text")." WHERE id = ".$ilDB->quote($id, "integer"));
			}
			$ilDB->query("UPDATE rep_robj_xtdo_tasks SET updated_by = ".$ilDB->quote($ilUser->getid(), "integer")." WHERE id = ".$ilDB->quote($id, "integer"));
		}
	}

//--------------------------------------------------------------------------------------------------------------------------------

	function setTabs()
	{
		global $ilTabs, $ilCtrl, $ilAccess;

		$this->myRefId=$this->object->getRefId();

		// tab for the "show content" command
		if ($ilAccess->checkAccess("read", "", $this->object->getRefId()))
		{
			$ilTabs->addTab("content", $this->txt("content"), $ilCtrl->getLinkTarget($this, "showContent"));
		}

		if ($ilAccess->checkAccess("write", "", $this->object->getRefId()))
		{
			$ilTabs->addTab("milestone", $this->txt("milestone"), $ilCtrl->getLinkTarget($this, "milestone"));
		}



		$this->addInfoTab();
		// standard info screen tab

		// a "properties" tab
		if ($ilAccess->checkAccess("write", "", $this->object->getRefId()))
		{
			$ilTabs->addTab("properties", $this->txt("properties"), $ilCtrl->getLinkTarget($this, "editProperties"));
		}

		// standard epermission tab
		$this->addPermissionTab();
	}

//--------------------------------------------------------------------------------------------------------------------------------

	function cancelmyDelete()
	{
		$this->showContent();
	}

	function delete_row_milestone()
	{
		global $ilCtrl,$lng,$tpl,$ilTabs;
		$ilTabs->activateTab("milestone");
		$conf =new ilConfirmationGUI();
		$conf->setFormAction($ilCtrl->getFormAction($this));
		$conf->setHeaderText($this->txt('reallydelete'));
		if(isset($_GET['data_id']))
		{
			$buffer =$_GET['data_id'];
		}
		$conf->addItem('data_id[]', $buffer , $this->txt('deletetxt'));
		$conf->setConfirm($lng->txt('delete'), 'deletemilestone');
		$conf->setCancel($lng->txt('cancel'), 'cancel_edit_milestone');
		$tpl->setContent($conf->getHTML());
	}

	function deletemilestone()
	{
		global $ilDB;
		if(isset($_POST['data_id']))
		{
			$ids =$_POST['data_id'];
		}
		foreach ($ids as $id)
		{
			$ilDB->manipulate("DELETE FROM  rep_robj_xtdo_milsto WHERE id = ".$ilDB->quote($id, "integer"));
			$ilDB->manipulate("DELETE FROM  rep_robj_xtdo_tasks WHERE milestone_id = ".$ilDB->quote($id, "integer"));
		}
		$this->cancel_edit_milestone();
	}




	function delete_row()
	{
		global $ilCtrl,$lng,$tpl,$ilTabs;
		$ilTabs->activateTab("content");
		$conf =new ilConfirmationGUI();
		$conf->setFormAction($ilCtrl->getFormAction($this));
		$conf->setHeaderText($this->txt('reallydelete'));
		if(isset($_GET['data_id']))
		{
			$buffer =$_GET['data_id'];
		}
		$conf->addItem('data_id[]', $buffer , $this->txt('deletetxt'));
		$conf->setConfirm($lng->txt('delete'), 'deletetask');
		$conf->setCancel($lng->txt('cancel'), 'cancelmyDelete');
		$tpl->setContent($conf->getHTML());
	}
	function deletetask()
	{
		global $ilDB;
		if(isset($_POST['data_id']))
		{
			$ids =$_POST['data_id'];
		}
		foreach ($ids as $id)
		{
			$ilDB->manipulate("DELETE FROM  rep_robj_xtdo_tasks WHERE id = ".$ilDB->quote($id, "integer"));
		}
		$this->showContent();
	}

//--------------------------------------------------------------------------------------------------------------------------------

	function editProperties()
	{
		global $tpl, $ilTabs;
		
		$ilTabs->activateTab("properties");
		$this->initPropertiesForm();
		$this->getPropertiesValues();

		if($this->is_collectlist())
		{
			$text_for_ID=$this->txt('text_befor_id').' '.'<b>'.$this->obj_id.'</b>'.'</br>'.$this->txt('text_after_id_collectlist');
		}

		$tpl->setContent($text_for_ID.$this->form->getHTML());
	}

	public function initPropertiesForm()
	{
		global $ilCtrl;
	
		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
		$this->form = new ilPropertyFormGUI();
		$this->form->setMultipart(true);
		$this->form->setTitle($this->txt("edit_properties"));
		$this->form->setFormAction($ilCtrl->getFormAction($this));
		$this->form->addCommandButton("updateProperties", $this->txt("save"));

		//------------------------------------------
		//Standard Einstellungen

		$ti = new ilTextInputGUI($this->txt("title"), "title");
		$ti->setRequired(true);
		$this->form->addItem($ti);

		// description
		$ta = new ilTextAreaInputGUI($this->txt("description"), "desc");
		$this->form->addItem($ta);
		
		// online
		$cb = new ilCheckboxInputGUI($this->lng->txt("online"), "online");
		$this->form->addItem($cb);
		//------------------------------------------
		//Spezielle nicht-Collectlist Einstellungen


		if(!$this->is_collectlist())
		{
			$section = new ilFormSectionHeaderGUI();
			$section->setTitle($this->txt('edit_no_collectlist'));
			$this->form->addItem($section);

			$ti = new ilTextInputGUI($this->txt("collectlist"), "collectlist");
			$ti->setInfo($this->txt("collectlist_info"));
			$this->form->addItem($ti);
		}


		//------------------------------------------
		//Spezielle Einstellungen

		$section = new ilFormSectionHeaderGUI();
		$section->setTitle($this->txt('edit_special'));
		$this->form->addItem($section);

		// option 1
		$ti = new ilCheckboxInputGUI($this->txt("are_finished_shown"), "are_finished_shown");
		$ti->setInfo($this->txt("are_finished_shown_info"));
		$this->form->addItem($ti);

        $ti = new ilCheckboxInputGUI($this->txt("before_startdate_shown"), "before_startdate_shown");
		$ti->setInfo($this->txt("before_startdate_shown_info"));
        $this->form->addItem($ti);

		$ti = new ilCheckboxInputGUI($this->txt("percent_line_option"), "percent_bar");
		$ti->setInfo($this->txt("percent_line_option_info"));
		$this->form->addItem($ti);
		//------------------------------------------
		//Felder Einstellungen

		$section = new ilFormSectionHeaderGUI();
		$section->setTitle($this->txt('edit_fields'));
		$this->form->addItem($section);

		$ti = new ilCheckboxInputGUI($this->txt('show_createdby'),'showcreatedby');
		$ti->setInfo($this->txt("show_createdby_info"));
		$this->form->addItem($ti);
		$ti = new ilCheckboxInputGUI($this->txt('show_updatedby'),'showupdatedby');
		$ti->setInfo($this->txt("show_updatedby_info"));
		$this->form->addItem($ti);
		$ti = new ilCheckboxInputGUI($this->txt('show_startdate'),'showstartdate');
		$ti->setInfo($this->txt("show_startdate_info"));
		$this->form->addItem($ti);


		//------------------------------------------
		//Enddatum Einstellungen
		$section = new ilFormSectionHeaderGUI();
		$section->setTitle($this->txt('enddate_props'));
		$this->form->addItem($section);

		$ti = new ilCheckboxInputGUI($this->txt('enddate_warning'),'enddate_warning');
		$ti->setInfo($this->txt("enddate_warning_info"));
		$this->form->addItem($ti);

		$ti = new ilCheckboxInputGUI($this->txt('enddate_cursive'),'enddate_cursive');
		$ti->setInfo($this->txt("enddate_cursive_info"));
		$this->form->addItem($ti);

		$ti = new ilCheckboxInputGUI($this->txt('enddate_fat'),'enddate_fat');
		$ti->setInfo($this->txt("enddate_fat_info"));
		$this->form->addItem($ti);

		$ti= new ilColorPickerInputGUI($this->txt('enddate_color'),'enddate_color');
		$ti->setInfo($this->txt("enddate_color_info"));
		$this->form->addItem($ti);
		//------------------------------------------
		//Status Einstellungen
		$section = new ilFormSectionHeaderGUI();
		$section->setTitle($this->txt('edit_status_props'));
		$this->form->addItem($section);

		$ti = new ilCheckboxInputGUI($this->txt("edit_status_checkbox"), "edit_status_checkbox");
		$ti->setInfo($this->txt("edit_status_checkbox_info"));
		$this->form->addItem($ti);

		$ti = new ilCheckboxInputGUI($this->txt("edit_status_permission"), "edit_status_permission");
		$ti->setInfo($this->txt("edit_status_permission_info"));
		$this->form->addItem($ti);

		$ti = new ilCheckboxInputGUI($this->txt("edit_status_position"), "edit_status_position");
		$ti->setInfo($this->txt("edit_status_position_info"));
		$this->form->addItem($ti);

	}

	function getPropertiesValues()
	{
		$values["title"] = $this->object->getTitle();
		$values["desc"] = $this->object->getDescription();
		$values["online"] = $this->object->getOnline();
		$values["are_finished_shown"] = $this->object->getAreFinishedShown();
        $values["before_startdate_shown"] = $this->object->getBeforeStartdateShown();
		$values["collectlist"] = $this->object->getCollectlistid();
		$values["percent_bar"] = $this->object->getShowHidePercentBarOption();
		$values["edit_status_checkbox"] = $this->object->getShowEditStatusButton();
		$values["edit_status_permission"] = $this->object->getEditStatusPermission();
		$values["showcreatedby"] = $this->object->getShowCreatedBy();
		$values["showupdatedby"] = $this->object->getShowUpdatedBy();
		$values["showstartdate"] = $this->object->getShowStartDate();
		$values["enddate_warning"] = $this->object->getEnddateWarning();
		$values["enddate_cursive"] = $this->object->getEnddateCursive();
		$values["enddate_fat"] = $this->object->getEnddateFat();
		$values["enddate_color"] = $this->object->getEnddateColor();
		$values["edit_status_position"]=$this->object->getStatusPosition();

		$this->form->setValuesByArray($values);


	}

	private function is_id_collectlist($id)
	{
		if($id=='')
		{
			return true;
		}

		global $ilDB;
		$sql_string="SELECT collectlist FROM rep_robj_xtdo_data WHERE id = ". $ilDB->quote($id,"integer");
		$result = $ilDB->query($sql_string);
		while ($record = $ilDB->fetchAssoc($result))
		{
			$is_collectlist=$record['collectlist'];
		}
		return $is_collectlist;

	}


	public function updateProperties()
	{

		global $tpl, $lng, $ilCtrl,$ilDB;

		$this->initPropertiesForm();
		if ($this->form->checkInput())
		{

			$input=$this->form->getInput("collectlist");
			$is_okay=false;


			if($this->is_id_collectlist($input))
			{
				$is_okay=true;
			}
			
			
			$this->object->setTitle($this->form->getInput("title"));
			$this->object->setDescription($this->form->getInput("desc"));
			$this->object->setAreFinishedShown($this->form->getInput("are_finished_shown"));
			$this->object->setBeforeStartdateShown($this->form->getInput("before_startdate_shown"));
			$this->object->setOnline($this->form->getInput("online"));
			$this->object->setCollectlistid($this->form->getInput("collectlist"));
			
			$this->object->setEnddateWarning($this->form->getInput("enddate_warning"));
			$this->object->setEnddateCursive($this->form->getInput("enddate_cursive"));
			$this->object->setEnddateFat($this->form->getInput("enddate_fat"));
			$this->object->setEnddateColor($this->form->getInput("enddate_color"));

			$this->object->SetShowCreatedBy($this->form->getInput("showcreatedby"));
			$this->object->SetShowUpdatedBy($this->form->getInput("showupdatedby"));
			$this->object->SetShowStartDate($this->form->getInput("showstartdate"));
			
			$this->object->setShowHidePercentBarOption($this->form->getInput("percent_bar"));
			$this->object->setShowEditStatusButton($this->form->getInput("edit_status_checkbox"));
			$this->object->setEditStatusPermission($this->form->getInput("edit_status_permission"));
			$this->object->setStatusPosition($this->form->getInput("edit_status_position"));
			if($is_okay) {
				$this->object->update();
				ilUtil::sendSuccess($lng->txt("msg_obj_modified"), true);
				$ilCtrl->redirect($this, "editProperties");
			}else
			{
				ilUtil::sendFailure($this->txt("failure_collectlist"),false);
				$this->form->setValuesByPost();
				$tpl->setContent($this->form->getHtml());
			}
		}

		$this->form->setValuesByPost();
		$tpl->setContent($this->form->getHtml());
	}


//--------------------------------------------------------------------------------------------------------------------------------
	//Setzt Form indem eine Aufgabe erstellt werden kann
	//Zudem Funktionen welche Prüfen ob mehr Optionen angezeigt werden sollen zum spezifizieren der Aufgabe
	//Sowie Bearbeitung des Inputs sowie schreiben der Daten in die Datenbank
	
	public function getMileStonesNames($array=array())
	{
		global $ilDB;
		$sql_string="SELECT milestone FROM rep_robj_xtdo_milsto WHERE ";
		$sql_string=$sql_string."objectid = ".$this->obj_id;

		$result = $ilDB->query($sql_string);
        while ($record = $ilDB->fetchAssoc($result))
        {          
            array_push($array,$record["milestone"]);
        }
		return $array;
	}
	
	
	public function inittodosForm()
	{
		global $ilCtrl,$ilSetting;

		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
		$this->form = new ilPropertyFormGUI();

		// title
		$ti = new ilTextInputGUI($this->txt("todo"), "todo");
		$ti->setRequired(true);
		$this->form->addItem($ti);


		if($this->is_collectlist())
		{
			$this->form->addCommandButton("addtaskall", $this->txt("addtaskall"));
		}
		$this->form->addCommandButton("newtask", $this->txt("newtask"));

		if($this->all) {

			$optionen=array($this->txt("nothing"));
			$optionen=$this->getMileStonesNames($optionen);
			$ti = new ilSelectInputGUI($this->txt("milestone_task"),"milestone_task");
            $ti->setOptions($optionen);
			$this->form->addItem($ti);
			$ti = new ilDateTimeInputGUI($this->txt("startdate"),'startdate');
			if($this->versionBigger52())  $ti->setMode(ilDateTimeInputGUI::MODE_INPUT);
			$this->form->addItem($ti);
			$ti = new ilDateTimeInputGUI($this->txt("enddate"),'enddate');
			if($this->versionBigger52()) $ti->setMode(ilDateTimeInputGUI::MODE_INPUT);
			$this->form->addItem($ti);
			$ti = new ilTextAreaInputGUI($this->txt("description"), "description");
			$this->form->addItem($ti);
			$this->form->addCommandButton("lessProperties",$this->txt('moreProperties'));
		}else
		{
			$this->form->addCommandButton("moreProperties",$this->txt('moreProperties'));
		}



			$this->form->setTitle($this->txt("new_task"));
			$this->form->setFormAction($ilCtrl->getFormAction($this));

	}


	function versionBigger52()
	{
		//prüfe ob Version größer 51 wenn nicht gibt true zurück um alte Funktionen aufzurufen
		global $ilSetting;
		if(strpos($ilSetting->get("ilias_version"),"2.0") == false)
		{
			return true;
		}
		else return false;
	}

	function getTodosValues()
	{
		global $lng,$ilSetting;
		$this->inittodosForm();
		if ($this->form->checkInput())
		{
			if($this->versionBigger52())
			{
					$startdate_array = $this->form->getInput("startdate");
					$enddate_array = $this->form->getInput("enddate");

					if (strtotime($startdate_array['date']) <= strtotime($enddate_array['date']) OR $enddate_array['date'] == "") {
						$values['task'] = $this->form->getInput("todo");
						$values['milestone_task'] = $this->form->getInput("milestone_task");
						$values['startdate'] = $startdate_array['date'];
						$values['enddate'] = $enddate_array['date'];
						$values['description'] = $this->form->getInput("description");
						ilUtil::sendSuccess($lng->txt("msg_obj_modified"), true);
					} else {
						ilUtil::sendFailure($lng->txt("form_input_not_valid"), true);
					}
			}else
			{
					$startdate_array = $this->form->getInput("startdate");
					$enddate_array = $this->form->getInput("enddate");
					if (strtotime($startdate_array) <= strtotime($enddate_array) OR $enddate_array == "") {
						$values['task'] = $this->form->getInput("todo");
						$values['milestone_task'] = $this->form->getInput("milestone_task");
						$values['startdate'] = $startdate_array;
						$values['enddate'] = $enddate_array;
						$values['description'] = $this->form->getInput("description");
						ilUtil::sendSuccess($lng->txt("msg_obj_modified"), true);
					} else {
						ilUtil::sendFailure($lng->txt("form_input_not_valid"), true);
					}
			}
		}
		return $values;
	}
	
	
	function getMilestoneIdWithName($milestone)
	{
		global $ilDB;
		if($milestone==$this->txt("nothing"))
		{
			return 0;
		}
		
		$sql_string="SELECT id FROM rep_robj_xtdo_milsto WHERE milestone =". $ilDB->quote($milestone,"text");
		$result = $ilDB->query($sql_string);
        while ($record = $ilDB->fetchAssoc($result))
        {          
            $milestone_id=$record["id"];
        }
		return $milestone_id;
	}

	function getMilestoneIdWithNameAndObjectId($milestone)
	{
		global $ilDB;
		if($milestone==$this->txt("nothing"))
		{
			return 0;
		}

		$sql_string="SELECT id FROM rep_robj_xtdo_milsto WHERE milestone =". $ilDB->quote($milestone,"text").' AND objectid = '. $ilDB->quote($this->object_id,"integer");
		$result = $ilDB->query($sql_string);
		while ($record = $ilDB->fetchAssoc($result))
		{
			$milestone_id=$record["id"];
		}
		return $milestone_id;
	}
	
	public function updatetodos($newTaskArray,$add_object_id)
	{
		global $ilDB;
		global $ilUser;
		$user = $ilUser->getid();
		if(!empty($newTaskArray)) {
			
			$optionen=array($this->txt("nothing"));
			$optionen=$this->getMileStonesNames($optionen);
			
			$option=$newTaskArray["milestone_task"];
			if($newTaskArray["milestone_task"] == "" OR $newTaskArray["milestone_task"] == NULL)
			{
				$option=0;
			}
			
			
			$milestone_id=$this->getMilestoneIdWithNameAndObjectId($optionen[$option]);

			if($newTaskArray["startdate"] != NULL)$startdate=date("Y-m-d",strtotime(str_replace('.', '-', $newTaskArray["startdate"]))); else $startdate = NULL;
			if($newTaskArray["enddate"] != NULL)$enddate=date("Y-m-d",strtotime(str_replace('.', '-', $newTaskArray["enddate"])));else $enddate = NULL;
			$ilDB->manipulateF("INSERT INTO rep_robj_xtdo_tasks (objectid, tasks, startdate, enddate, description,edit_status,created_by,milestone_id) VALUES " .
				" (%s,%s,%s,%s,%s,%s,%s,%s)",
				array("integer", "text", "text", "text", "text", "boolean","integer","integer"),
				array($add_object_id, $newTaskArray['task'], $startdate,
					$enddate, $newTaskArray['description'], false,$user,$milestone_id)
			);
		}
	}


	function initAddTaskAllForm($data = array(),$list_ids)
	{
		global $ilCtrl,$lng;

		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
		$this->form = new ilPropertyFormGUI();
		$this->form->setMultipart(true);
		$this->form->addCommandButton("addtoalllist",$this->txt('new_task_all'));
		$this->form->addCommandButton("cancel_edit",$lng->txt('cancel'));
		$this->form->setTitle($this->txt("really_add"));
		$this->form->setFormAction($ilCtrl->getFormAction($this));



		$ti= new ilNonEditableValueGUI($this->txt("todo"),"todo");
		$ti->setValue($data["task"]);
		$this->form->addItem($ti);

		$ti= new ilNonEditableValueGUI($this->txt("startdate"),"startdate");
		$ti->setValue($data["startdate"]);
		$this->form->addItem($ti);

		$ti= new ilNonEditableValueGUI($this->txt("enddate"),"enddate");
		$ti->setValue($data["enddate"]);
		$this->form->addItem($ti);

		$ti= new ilNonEditableValueGUI($this->txt("description"),"description");
		$ti->setValue($data["description"]);
		$this->form->addItem($ti);


		$section = new ilFormSectionHeaderGUI();
		$section->setTitle($this->txt('choosemilestone'));
		$this->form->addItem($section);

		foreach($list_ids as $id)
		{
			$listname=$this->getListNameWithId($id);
			$listname_postvar=str_replace(" ",'_',$this->getListNameWithId($id));
			$optionen=array($this->txt("nothing"));
			$optionen=$this->getMileStonesFromList($optionen,$id);
			$ti = new ilSelectInputGUI($listname,$listname_postvar);
			$ti->setOptions($optionen);
			$this->form->addItem($ti);
		}


	}

	function getAddAllValues($daten)
	{
		global $ilDB;
		$this->initAddTaskAllForm($daten,$this->get_all_list_ids_for_collect_list());
		if ($this->form->checkInput())
		{
			$value["task"]=$this->form->getInput("todo");
			$value["startdate"]=$this->form->getInput("startdate");
			$value["enddate"]=$this->form->getInput("enddate");
			$value["description"]=$this->form->getInput("description");
			foreach ($this->get_all_list_ids_for_collect_list() as $id)
			{
				$listname=str_replace(" ",'_',$this->getListNameWithId($id));
				
				$value[$listname]=$this->form->getInput($listname);

				if($value[$listname]!=0)
				{
					$optionen=array($this->txt("nothing"));
					$optionen=$this->getMileStonesFromList($optionen,$id);
					$value[$listname]=$optionen[$value[$listname]];
					$value[$listname] = $this->getMilestoneIdWithNameAndOtherObjectId($value[$listname],$id);
				}else
				{
					$value[$listname]=0;
				}

			}
		}
		return $value;
	}
	function getMilestoneIdWithNameAndOtherObjectId($milestone,$object_id)
	{
		global $ilDB;
		if($milestone==$this->txt("nothing"))
		{
			return 0;
		}

		$sql_string="SELECT id FROM rep_robj_xtdo_milsto WHERE milestone =". $ilDB->quote($milestone,"text").' AND objectid = '. $ilDB->quote($object_id,"integer");

		$result = $ilDB->query($sql_string);
		while ($record = $ilDB->fetchAssoc($result))
		{
			$milestone_id=$record["id"];
		}
		return $milestone_id;
	}

	public function updatealltodos($values_array)
	{
		global $ilDB,$ilUser;
		//$user = $ilUser->getLoginByUserId($ilUser->getid());
		$user = $ilUser->getid();
		$list_ids = $this->get_all_list_ids_for_collect_list();
		$ids = array();
		$count = 0;
		foreach ($values_array as $value)
		{
			if($count >= 4)
			{
				array_push($ids,$value);
			}
			$count++;
		}


		$count=0;
		foreach ($list_ids as $id)
		{
			if($values_array["startdate"] != NULL)$startdate=date("Y-m-d",strtotime(str_replace('.', '-', $values_array["startdate"]))); else $startdate = NULL;
			if($values_array["enddate"] != NULL)$enddate=date("Y-m-d",strtotime(str_replace('.', '-', $values_array["enddate"])));else $enddate = NULL;

			$ilDB->manipulateF("INSERT INTO rep_robj_xtdo_tasks (objectid, tasks, startdate, enddate, description,edit_status,created_by,milestone_id) VALUES " .
				" (%s,%s,%s,%s,%s,%s,%s,%s)",
				array("integer", "text", "text", "text", "text", "boolean","integer","integer"),
				array($id, $values_array["task"],$startdate ,
					$enddate, $values_array["description"], false,$user,$ids[$count])
			);
			$count++;
		}
	}




	function addtoalllist()
	{
		$this->updatealltodos($this->getAddAllValues(array()));
		$this->showContent();
	}


	private function getListNameWithId($id)
	{
		global $ilDB;
		$sql_string = "SELECT namen FROM rep_robj_xtdo_data WHERE id = ".$ilDB->quote($id,"integer");
		$result = $ilDB->query($sql_string);
		$namen="";
		while($record = $ilDB->fetchAssoc($result))
		{
			$namen = $record["namen"];
		}
		return $namen;
	}


	public function getMileStonesFromList($array=array(),$id)
	{
		global $ilDB;
		$sql_string="SELECT milestone FROM rep_robj_xtdo_milsto WHERE ";
		$sql_string=$sql_string."objectid = ".$id;
		$result = $ilDB->query($sql_string);
		while ($record = $ilDB->fetchAssoc($result))
		{
			array_push($array,$record["milestone"]);
		}
		return $array;
	}

	function addtaskall()
	{
		global $tpl,$ilCtrl,$ilTabs;
		$this->set_is_moreprops_open();
		$array_with_data=$this->getTodosValues();
		$all_list_ids=$this->get_all_list_ids_for_collect_list();
		$ilTabs->activateTab("content");
		if($array_with_data)
		{
			$this->initAddTaskAllForm($array_with_data, $all_list_ids);
			$tpl->setContent($this->form->getHTML());
		}
		else
		{
			$this->showContent();
		}
	}

	function newtask()
	{
		$this->set_is_moreprops_open();
		$this->updatetodos($this->getTodosValues(),$this->obj_id);
		$this->showContent();
	}

	function moreProperties()
	{
		$this->all=true;
		$this->set_is_moreprops_open();
		$this->showContent();
	}

	function lessProperties()
	{
		$this->all=false;
		$this->set_is_moreprops_open();
		$this->showContent();
	}

	function set_is_moreprops_open()
	{
		global $ilCtrl;
		if($this->all)
		{
			$set=1;
		}else
		{
			$set=2;
		}
		$ilCtrl->setParameterByClass("ilObjTodolistsGUI", "open",$set);
	}
//--------------------------------------------------------------------------------------------------------------------------------
	// Setzen der Prozentanzeige welche anzeigt wieviel Prozent der Aufgaben bereits abgearbeitet wurden
	// Dabei wird berücksichtigt ob es sich um eine Sammelliste handelt oder nicht

	private function showpercent()
	{
		if($this->is_percent_bar_shown())
		{
			return $this->choose_percentbar();
		}else
		{
			return '';
		}

	}

	private function is_percent_bar_shown()
	{
		global $ilDB;
		$sql_string="SELECT show_percent_bar FROM rep_robj_xtdo_data WHERE id = ". $ilDB->quote($this->obj_id,"integer");
		$result = $ilDB->query($sql_string);
		while ($record = $ilDB->fetchAssoc($result)) {
			$show=$record["show_percent_bar"];
		}
		return $show;
	}

    private function choose_percentbar()
	{

		if(!$this->is_collectlist())
		{
			return $this->showpercent_normal();
		}else
		{
			return $this->showpercent_collectlist();
		}
	}

	private function is_collectlist()
	{
		global $ilDB;
		$sql_string="SELECT collectlist FROM rep_robj_xtdo_data WHERE id = ". $ilDB->quote($this->obj_id,"integer");
		$result = $ilDB->query($sql_string);
		while ($record = $ilDB->fetchAssoc($result))
		{
			$is_collectlist=$record['collectlist'];
		}
		return $is_collectlist;
	}

	private function percent_bar($anzahl_alle,$anzahl_fertig)
	{
		if($anzahl_alle != 0)
		{
			$fertig_percent = round(($anzahl_fertig / $anzahl_alle) * 100, 0, PHP_ROUND_HALF_UP);
		}else
		{
			$fertig_percent=100;
		}
		$px_percent = (40 / 100) * $fertig_percent;
		$width_green = $px_percent . '%';
		$red_percent = 40 - $px_percent;
		$width_red = $red_percent . '%';
		$height = '20px';

		$image = '<img src="Customizing/global/plugins/Services/Repository/RepositoryObject/Todolists/templates/images/green_v1.png" height="' . $height . '" width="' . $width_green . '">';
		$image = $image . '<img src="Customizing/global/plugins/Services/Repository/RepositoryObject/Todolists/templates/images/orange_v1.png" height="' . $height . '" width="' . $width_red . '">';
		$text = ' ' . $fertig_percent . '% ' . $this->txt('percentbar_text');
		return $image . $text;
	}

	private function count_sql_result($sql_result)
	{
		global $ilDB;
		$count = 0;
		while ($record = $ilDB->fetchAssoc($sql_result)) {
			$count++;
		}
		return $count;
	}

	private function showpercent_normal()
	{
		global $ilDB;

			$sql_string = "SELECT edit_status FROM rep_robj_xtdo_tasks WHERE milestone_id=0 AND objectid = " . $ilDB->quote($this->obj_id, "integer") . ' AND edit_status = ' . $ilDB->quote(1, "integer");
			$result = $ilDB->query($sql_string);
			$fertig = $this->count_sql_result($result);

			$sql_string = "SELECT edit_status FROM rep_robj_xtdo_tasks WHERE milestone_id=0 AND objectid = " . $ilDB->quote($this->obj_id, "integer");
			$result = $ilDB->query($sql_string);
			$alle = $this->count_sql_result($result);

			return $this->percent_bar($alle,$fertig);
	}

	private function showpercent_collectlist()
	{
		global $ilDB;

			$id_array=$this->get_all_list_ids_for_collect_list();
			$sql_string = "SELECT edit_status FROM rep_robj_xtdo_tasks WHERE milestone_id=0 AND (";

			$count=0;
			foreach ($id_array as $id)
			{
				if($count != 0 AND $count < count($id_array))$sql_string=$sql_string.' OR ';
				$sql_string=$sql_string." objectid = " . $ilDB->quote($id, "integer");
				$count++;
			}
			$sql_string=$sql_string.' )';
			$sql_string_fertig=$sql_string.' AND edit_status = ' . $ilDB->quote(1, "integer");
		
			if($count != 0) {
				$result = $ilDB->query($sql_string);
				$alle = $this->count_sql_result($result);
				$result = $ilDB->query($sql_string_fertig);
				$fertig = $this->count_sql_result($result);
			}else
			{
				$alle=1;$fertig=0;
			}


			return $this->percent_bar($alle,$fertig);
	}

	private function get_all_list_ids_for_collect_list()
	{
		global $ilDB;
		$sql_string="SELECT id FROM rep_robj_xtdo_data WHERE get_collect = ". $ilDB->quote($this->obj_id,"integer");
		$result = $ilDB->query($sql_string);
		$id_array=array();
		while ($record = $ilDB->fetchAssoc($result)) {
			array_push($id_array,$record["id"]);
		}
		return $id_array;
	}


//--------------------------------------------------------------------------------------------------------------------------------

	function showContent()
	{
		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
		global $tpl, $ilTabs,$ilAccess;
		$ilTabs->activateTab("content");




		$html_content='';

		if ($ilAccess->checkAccess("add_entry", "", $this->object->getRefId()))
		{
			$this->inittodosForm();
			$html_content=$html_content.$this->form->getHTML();
		}
		
		$html_content=$html_content.$this->showpercent();


		$bufffer=$this->getTable();

		$bufffer=$bufffer.$this->getMilestoneTable();
		
		
		
		$html_content=$html_content.$bufffer;


		$tpl->setContent($html_content);
	}
	
	
	function getMilestoneTable()
	{
		include_once ('class.ilMilestoneTaskListGUI.php');
		global $ilDB;

		$htmlCode="";

		$columnnames=array($this->txt('task'),$this->txt('startdate'),$this->txt('enddate'),$this->txt('description'),$this->txt('createdby'),$this->txt('updatedby'),
						   $this->txt('attached_to'),$this->txt('status'),$this->txt('status_change'),$this->txt('startdate_before'));

		$optionen=array($this->txt('both'),$this->txt('done'),$this->txt('undone'));


		$sql_string="SELECT objectid,milestone,id FROM rep_robj_xtdo_milsto WHERE ";
		if(!$this->is_collectlist())$sql_string=$sql_string."objectid = ".$this->obj_id;
		if($this->is_collectlist())$sql_string=$sql_string.$this->addCollectlistString();
		$result = $ilDB->query($sql_string);

		
		
		while ($record = $ilDB->fetchAssoc($result))
		{

			if($record["objectid"]!=$this->object_id)
			{
				$sql_string_two = "SELECT namen FROM rep_robj_xtdo_data WHERE ";
				$sql_string_two = $sql_string_two . "id = " . $record["objectid"];
				$another_result = $ilDB->query($sql_string_two);
				while ($record_two = $ilDB->fetchAssoc($another_result))$namen = ' ['.$record_two["namen"].']';
			}else
			{
				$namen ="";
			}

			$table = new ilMilestoneTaskListGUI(str_replace(" ","_",$record["milestone"]).$namen,$record["milestone"].$namen,$columnnames,$optionen,$this->myRefId,$record["objectid"]);

			if(isset($_SESSION[str_replace(" ","_",$record["milestone"])."_filter"]))
			{
				$filter=$_SESSION[str_replace(" ","_",$record["milestone"])."_filter"];
			}
			
			if(isset($_SESSION[str_replace(" ","_",$record["milestone"])."_cleartable"]))
			{
				if($_SESSION[str_replace(" ","_",$record["milestone"])."_cleartable"])
				{
					$table->resetOffset();
				}
				unset($_SESSION[str_replace(" ","_",$record["milestone"])."_cleartable"]);
			}
			
			$table->setMilestoneId($record["id"]);
			$table->getDataFromDb($filter);
			$htmlCode=$htmlCode.$table->getHTML();
		}
		

		return $htmlCode;
		
	}


	function getIdsForCollectTheirMilestones()
	{
		global $ilDB;
		$sql_buffer="SELECT id FROM rep_robj_xtdo_data WHERE get_collect = ".$ilDB->quote($this->object_id,"integer");
		$result = $ilDB->query($sql_buffer);
		$ids=array();
		while ($record = $ilDB->fetchAssoc($result))
		{
			array_push($ids,$record['id']);
		}
		array_push($ids,$this->object_id);
		return $ids;
	}



	function addCollectlistString()
	{
		if($this->is_collectlist())
		{
			$ids =$this->getIdsForCollectTheirMilestones();
			global $ilDB;
			if(count($ids)!=0)
			{
				$count=0;
				$sql_string='(';
				foreach($ids as $id)
				{
					if($count==0)
					{
						$sql_string=$sql_string.  " objectid = ". $ilDB->quote($id,"integer");
					}else
					{
						$sql_string=$sql_string.  " OR objectid = ". $ilDB->quote($id,"integer");
					}
					$count++;
				}
				$sql_string=$sql_string.')';
				return $sql_string;
			}
			return '';
		}

		return "";
	}





	function applyMilestoneFilter()
	{
		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
		include_once ('class.ilMilestoneTaskListGUI.php');
		global $tpl, $ilTabs, $lng,$ilCtrl;

		$columnnames=array($this->txt('task'),$this->txt('startdate'),$this->txt('enddate'),$this->txt('description'),$this->txt('createdby'),$this->txt('updatedby'),
			$this->txt('attached_to'),$this->txt('status'),$this->txt('status_change'),$this->txt('startdate_before'));

		$optionen=array($this->txt('both'),$this->txt('done'),$this->txt('undone'));


		$ilTabs->activateTab("content");


		$id="";
		foreach ($_POST as $key => $value) {
			$id=$key; break;
		}

		$id=substr($id,0,strlen($id)-6);

		$meilenstein_id=$id;
		$meilenstein_name=str_replace('_',' ',$id);

		$_SESSION["Tabel_id"]=$meilenstein_id;
		$_SESSION[$meilenstein_id."_filter"]=true;

		$table = new ilMilestoneTaskListGUI($meilenstein_id,$meilenstein_name,$columnnames,$optionen,$this->myRefId,$this->obj_id);
		$table->applyFilter();
	}

	function resetMilestoneFilter()
	{
		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
		global $tpl, $ilTabs, $lng,$ilCtrl;

		$ilTabs->activateTab("content");
		include_once ('class.ilMilestoneTaskListGUI.php');
		$columnnames=array($this->txt('task'),$this->txt('startdate'),$this->txt('enddate'),$this->txt('description'),$this->txt('createdby'),$this->txt('updatedby'),
			$this->txt('attached_to'),$this->txt('status'),$this->txt('status_change'),$this->txt('startdate_before'));

		$optionen=array($this->txt('both'),$this->txt('done'),$this->txt('undone'));



		if(isset($_SESSION["Tabel_id"]))
		{
			$meilenstein_id = $_SESSION["Tabel_id"];
			unset($_SESSION["Tabel_id"]);
			$meilenstein_name = str_replace("_", ' ', $meilenstein_id);
		}
		$_SESSION[$meilenstein_id."_filter"]=false;
		$_SESSION[$meilenstein_id."_cleartable"]=true;

		$table = new ilMilestoneTaskListGUI($meilenstein_id,$meilenstein_name,$columnnames,$optionen,$this->myRefId,$this->obj_id);
		$table->resetFilter();
	}
	
	function getTable()
	{
		include_once ('class.ilTaskListGUI.php');

		$columnnames=array($this->txt('task'),$this->txt('startdate'),$this->txt('enddate'),$this->txt('description'),$this->txt('createdby'),$this->txt('updatedby'),
						   $this->txt('attached_to'),$this->txt('status'),$this->txt('status_change'),$this->txt('startdate_before'));

		$optionen=array($this->txt('both'),$this->txt('done'),$this->txt('undone'));

		if(isset($_SESSION['own_filter']))
		{
			$filter=$_SESSION['own_filter'];
		}

		if(isset($_SESSION['clear_table']))
		{
			$clear=$_SESSION['clear_table'];
			unset($_SESSION['clear_table']);
		}

		$tasklist_content=new ilTaskListGUI($columnnames,$this->obj_id,$this->myRefId,$optionen,$this->txt("the_list"));
		if($clear)
		{
			$tasklist_content->resetOffset();
		}
		$tasklist_content->getDataFromDb($filter);
		return $tasklist_content->getTable();
	}
	function applyFilter()
	{
		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
		global $tpl, $ilTabs, $lng,$ilCtrl;

		$ilTabs->activateTab("content");
		$columnnames=array($this->txt('task'),$this->txt('startdate'),$this->txt('enddate'),$this->txt('description'),$this->txt('createdby'),$this->txt('updatedby'),
			$this->txt('attached_to'),$this->txt('status'),$this->txt('status_change'),$this->txt('startdate_before'));

		$optionen=array($this->txt('both'),$this->txt('done'),$this->txt('undone'));
		include_once ('class.ilTaskListGUI.php');

		$tasklist_content=new ilTaskListGUI($columnnames,$this->obj_id,$this->myRefId,$optionen,$this->txt("the_list"));
		$_SESSION['own_filter']=true;
		$tasklist_content->applyFilter();

	}

	function resetFilter()
	{
		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
		global $tpl, $ilTabs, $lng,$ilCtrl;

		$ilTabs->activateTab("content");
		$columnnames=array($this->txt('task'),$this->txt('startdate'),$this->txt('enddate'),$this->txt('description'),$this->txt('createdby'),$this->txt('updatedby'),
			$this->txt('attached_to'),$this->txt('status'),$this->txt('status_change'),$this->txt('startdate_before'));

		$optionen=array($this->txt('both'),$this->txt('done'),$this->txt('undone'));
		include_once ('class.ilTaskListGUI.php');

		$tasklist_content=new ilTaskListGUI($columnnames,$this->obj_id,$this->myRefId,$optionen,$this->txt("the_list"));
		$ilCtrl->setParameterByClass("ilObjTodolistsGUI", "filter",0);
		$_SESSION['own_filter']=false;
		$_SESSION['clear_table']=true;
		$tasklist_content->resetFilter();
	}
//--------------------------------------------------------------------------------------------------------------------------------
	function initMilestoneForm()
	{
		global $ilCtrl;

		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
		$this->form = new ilPropertyFormGUI();

		$ti = new ilTextInputGUI($this->txt("milestone"), "milestone");
		$ti->setRequired(true);
		$this->form->addItem($ti);

		$ti = new ilTextAreaInputGUI($this->txt("description"), "milestone_description");
		$this->form->addItem($ti);

		if($this->is_collectlist())
		{
			$this->form->addCommandButton("addmilestoneall", $this->txt("addmilestoneall"));
		}
		$this->form->addCommandButton("newmilestone", $this->txt("newmilestone"));

		$this->form->setTitle($this->txt("new_milestone"));
		$this->form->setFormAction($ilCtrl->getFormAction($this));
	}

	function getMilestoneFormInput()
	{
		global $lng;
		$this->initMilestoneForm();
		if ($this->form->checkInput())
		{
				$values['milestone'] = $this->form->getInput("milestone");
				$values['milestone_description'] = $this->form->getInput("milestone_description");
		}
		return $values;
	}


	function newmilestone()
	{
		global $ilDB,$ilUser,$ilCtrl;
		$form_input=$this->getMilestoneFormInput();
		//$user = $ilUser->getLoginByUserId($ilUser->getid());
		$user = $ilUser->getid();

		global $ilDB;
		$sql_buffer="SELECT id FROM rep_robj_xtdo_milsto WHERE milestone = ".$ilDB->quote($form_input['milestone'],"text");
		$result = $ilDB->query($sql_buffer);
		$count = 0;
		while ($record = $ilDB->fetchAssoc($result))
		{
			$count++;
		}

		if($count != 0)
		{
			unset($form_input);
		}


		if(!empty($form_input))
		{
			$ilDB->manipulateF("INSERT INTO rep_robj_xtdo_milsto (objectid, milestone, description,progress,created_by) VALUES " .
				" (%s,%s,%s,%s,%s)",
				array("integer", "text", "text", "integer", "integer"),
				array($this->obj_id,$form_input['milestone'],$form_input['milestone_description'],100,$user)
			);
		}else
		{
			ilUtil::sendFailure($this->txt('doublemilestone'),true);
		}
		$ilCtrl->redirect($this, 'milestone');
	}


	function getIdsForCollectTheirTasks()
	{
		global $ilDB;
		$sql_buffer="SELECT id FROM rep_robj_xtdo_data WHERE get_collect = ".$ilDB->quote($this->obj_id,"integer");
		$result = $ilDB->query($sql_buffer);
		$ids=array();
		while ($record = $ilDB->fetchAssoc($result))
		{
			array_push($ids,$record['id']);
		}
		return $ids;
	}

	function addmilestoneall()
	{
		global $ilDB,$ilUser,$ilCtrl;
		$form_input=$this->getMilestoneFormInput();
		$user = $ilUser->getid();
		$ids=$this->getIdsForCollectTheirTasks();

		foreach ($ids as $id)
		{

			global $ilDB;
			$sql_buffer="SELECT progress FROM rep_robj_xtdo_milsto WHERE milestone = ".$ilDB->quote($form_input['milestone'],"text").' AND objectid = '.$ilDB->quote($id,"integer");
			$result = $ilDB->query($sql_buffer);
			$count = 0;
			while ($record = $ilDB->fetchAssoc($result))
			{
				$count++;
			}

			if($count != 0)
			{
				unset($form_input);
			}

			if(!empty($form_input))
			{
				$ilDB->manipulateF("INSERT INTO rep_robj_xtdo_milsto (objectid, milestone, description,progress,created_by) VALUES " .
					" (%s,%s,%s,%s,%s)",
					array("integer", "text", "text", "integer", "integer"),
					array($id, $form_input['milestone'], $form_input['milestone_description'], 100, $user)
				);
			}else
			{
				ilUtil::sendFailure($this->txt('doublemilestone'),true);
				$ilCtrl->redirect($this, 'milestone');
			}
		}
		$ilCtrl->redirect($this, 'milestone');
	}

	function milestone()
	{
		global $tpl, $ilTabs,$ilAccess;
		$ilTabs->activateTab("milestone");
		include_once("class.ilMilestoneListGUI.php");

		$columnnames=array($this->txt("milestone"),$this->txt('startdate'),$this->txt('enddate'),$this->txt('description'),$this->txt('createdby'),$this->txt('updatedby'),
			$this->txt('attached_to'),$this->txt("progress"));


		$milestonetable=new ilMilestoneListGUI($this->txt("milestone"),$this->obj_id,$columnnames);

		$this->initMilestoneForm();



		$content=$this->form->getHTML();
		$content=$content.$milestonetable->getHtml();
		$tpl->setContent($content);


	}


	function resetMilestoneListFilter()
	{
		global $ilTabs;
		$ilTabs->activateTab("milestone");
		include_once("class.ilMilestoneListGUI.php");
		$columnnames=array($this->txt("milestone"),$this->txt('startdate'),$this->txt('enddate'),$this->txt('description'),$this->txt('createdby'),$this->txt('updatedby'),
			$this->txt('attached_to'),$this->txt("progress"));


		$milestonetable=new ilMilestoneListGUI($this->txt("milestone"),$this->obj_id,$columnnames);
		$milestonetable->resetFilter();
	}
	function applyMilestoneListFilter()
	{
		global $ilTabs;
		$ilTabs->activateTab("milestone");
		include_once("class.ilMilestoneListGUI.php");

		$columnnames=array($this->txt("milestone"),$this->txt('startdate'),$this->txt('enddate'),$this->txt('description'),$this->txt('createdby'),$this->txt('updatedby'),
			$this->txt('attached_to'),$this->txt("progress"));
		$milestonetable=new ilMilestoneListGUI($this->txt("milestone"),$this->obj_id,$columnnames);
		$milestonetable->applyFilter();
	}

}
?>
