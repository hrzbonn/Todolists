<?php
include_once("class.ilTask.php");
include_once ('class.ilAdvancedButtonGUI.php');

class ilTaskGUI
{
    private $sql_string;
    private $object;
    private $tasks;
    private $refId;
    private $table_id;
    private $counter;
    private $object_id;
    private $heigthAndWidth;
    private $direction;
    private $edit_acces;
    
    function __construct($sqlString,$object,$ref_id,$table_id,$object_id,$direction_array,$edit_acces)
    {
        $this->sql_string=$sqlString;
        $this->edit_acces=$edit_acces;
        $this->object=$object;
        $this->refId;
        $this->table_id=$table_id;
        $this->counter=0;
        $this->object_id=$object_id;
        $this->heigthAndWidth="100%";
        $this->direction=$direction_array;
        $this->setTasks();
    }
    function getTasks()
    {
        $tasks_buffer=array();
        $output_array=array();
        $tasks=$this->tasks;
        
        foreach ($tasks as $task)
        {
            foreach ($this->direction as $dir)
            {
                switch ($dir)
                {
                    case "tasks":
                        $tasks_buffer[$dir] = $task->getTask();
                        break;
                    case "edit_status":
                        $tasks_buffer[$dir] = $task->getEditStatus();
                        break;
                    case "startdate":
                        $tasks_buffer[$dir] = $task->getStartdate();
                        break;
                    case "enddate":
                        $tasks_buffer[$dir] = $task->getEnddate();
                        break;
                    case "description":
                        $tasks_buffer[$dir] = $task->getDescripiton();
                        break;
                    case "created_by":
                        $tasks_buffer[$dir] = $task->getCreatedby();
                        break;
                    case "updated_by":
                        $tasks_buffer[$dir] = $task->getUpdatedby();
                        break;
                    case "attechedto":
                        $tasks_buffer[$dir] = $task->getCollectListLink();
                        break;
                    case "button":
                        $tasks_buffer[$dir] = $task->getButton();
                        break;
                }
            }
            $tasks_buffer["id"] = $task->getId();
            array_push($output_array, $tasks_buffer);
        }
        return $output_array;
    }
    private function setTasks()
    {
        global $ilDB,$ilUser;
        $tasks = array();
        if($this->sql_string != '')
        {
            $result = $ilDB->query($this->sql_string);
            while ($record = $ilDB->fetchAssoc($result))
            {
                $new_task=new ilTask();

                if($this->object->getIsCollectlist())$new_task->setCollectListLink($this->CollectListLink($record["id"]));
                if ($this->object->getShowEditStatusButton())$new_task->setButton($this->setChangeButton($record["id"],$record['edit_status']));
                if (isset($record['id']))$new_task->setId($record["id"]);
                
                foreach ($this->direction as $dir)
                {
                    if( isset($record[$dir]) )
                    {
                        switch ($dir)
                        {
                            case 'tasks':
                                $new_task->setTask($record[$dir]);
                                break;
                            case 'startdate':
                                $record[$dir] = $this->formatDate($record[$dir]);
                                $new_task->setStartdate($record[$dir]);
                                break;
                            case 'enddate':
                                $enddate_time_stamp = strtotime( $record[$dir] );
                                $record[$dir] = $this->formatDate($record[$dir]);
                                if ($this->object->getEnddateWarning() AND $enddate_time_stamp < time())
                                    $record[$dir] = $this->changeDate($record[$dir]);
                                $new_task->setEnddate($record[$dir]);
                                break;
                            case 'description':
                                $new_task->setDescription($record[$dir],$this->table_id,$this->counter);
                                $this->counter=$this->counter+1;
                                break;
                            case 'edit_status':
                                $record[$dir] = $this->getWorkStatus($record[$dir], $record['id']);
                                $new_task->setEditStatus($record[$dir]);
                                break;
                            case 'created_by':
                                $new_task->setCreatedby($ilUser->getLoginByUserId($record[$dir]));
                                break;
                            case 'updated_by':
                                $new_task->setUpdatedby($ilUser->getLoginByUserId($record[$dir]));
                        }
                    }
                }
                array_push($tasks,$new_task);
            }
            $this->tasks=$tasks;
        }
    }

    private function setChangeButton($id,$status)
    {
        global $lng,$ilCtrl;
        $ilCtrl->setParameterByClass('ilObjTodolistsGUI', "id",$id);
        $alist = new ilAdvancedButtonGUI();
        $alist->setId($id);

        $first_link= $ilCtrl->getLinkTargetByClass('ilObjTodolistsGUI', 'changestatus');

        $id_pos=strpos($first_link,'&');
        $second_link=substr($first_link,$id_pos);
        $first_link=substr($first_link,0,$id_pos);
        $link= $first_link.'&status='.$status.$second_link;

        $alist->setListTitle($lng->txt("change"));
        $alist->addItem($lng->txt('edit'), 'changestatus',$link);
        return $alist->getHTML();
    }

    private function CollectListLink($task_id)
    {
        if($this->isTaskFromMilestone($task_id))return "";
        else return $this->getWorklistLinkForCollectlist($task_id);
    }


    private function getObjectIdWithTaskId($task_id)
    {
        global $ilDB;
        $sql_buffer = "SELECT objectid FROM rep_robj_xtdo_tasks WHERE id = " . $ilDB->quote($task_id, "integer");
        $result = $ilDB->query($sql_buffer);
        while ($record = $ilDB->fetchAssoc($result)) {
            $object_id = $record["objectid"];
        }
        return $object_id;
    }


    private function getWorkListNameWithTaskId($task_id)
    {
        global $ilDB;

        $object_id=$this->getObjectIdWithTaskId($task_id);
        $sql_buffer = "SELECT namen FROM rep_robj_xtdo_data WHERE id = " . $ilDB->quote($object_id, "integer");
        $result = $ilDB->query($sql_buffer);
        while ($record = $ilDB->fetchAssoc($result)) {
            $name = $record["namen"];
        }

        $ownname = $this->SqlSelectQueryOneValue('namen');

        if($name != $ownname)
        {
            //return $name." (ID = ".$object_id.")";
            return $name;
        }
        return '';

    }


    private function SqlSelectQueryOneValue($database_name)
    {
        global $ilDB;
        $sql_string="SELECT ".$database_name." FROM rep_robj_xtdo_data WHERE id = ".$ilDB->quote($this->object_id,"integer");
        $result = $ilDB->query($sql_string);
        while ($record = $ilDB->fetchAssoc($result))
        {
            $wert=$record[$database_name];
        }
        return $wert;
    }


    private function getWorklistLinkForCollectlist($task_id)
    {
        $worklistname=$this->getPath($this->getObjectIdWithTaskId($task_id)).'->'.$this->getWorkListNameWithTaskId($task_id);
        $ref_id=$this->getLinkRefId($this->getObjectIdWithTaskId($task_id));


        $add_link = $_SERVER["REQUEST_URI"];
        $add_link =substr($add_link,strpos($add_link,"cmdNode=")+8);


        $link="ilias.php?ref_id=".$ref_id."&cmd=showContent&cmdClass=ilobjtodolistsgui&cmdNode=".$add_link;
        if($this->getWorkListNameWithTaskId($task_id) != "" OR $this->getWorkListNameWithTaskId($task_id) != NULL)
            return "<a href='".$link."'>".$worklistname."<a>";
        else return "";
    }


    private function getLinkRefId($obj_id)
    {
        global $ilDB;
        $sql_string="SELECT ref_id FROM object_reference WHERE obj_id = ".$ilDB->quote($obj_id,"integer");
        $result = $ilDB->query($sql_string);
        while ($record = $ilDB->fetchAssoc($result))
        {
            $wert = $record["ref_id"];
        }
        return $wert;
    }



    private function getPath($object_id)
    {
        global $ilDB;

        $sql_string="SELECT path FROM rep_robj_xtdo_data WHERE id = ".$ilDB->quote($object_id,"integer");
        $result = $ilDB->query($sql_string);
        while ($record = $ilDB->fetchAssoc($result))
        {
            $wert=$record["path"];
        }
        return $wert;
    }


    private function isTaskFromMilestone($task_id)
    {
        global $ilDB;
        $sql_buffer = "SELECT milestone_id FROM rep_robj_xtdo_tasks WHERE id = " . $ilDB->quote($task_id, "integer");
        $result = $ilDB->query($sql_buffer);
        while ($record = $ilDB->fetchAssoc($result)) {
            $milestone_id = $record["milestone_id"];
        }
        if($milestone_id == 0)return false;
        else return true;
    }




    private function timestampInDate($timestamp)
    {
        if ($timestamp != 0) {
            $timestamp = date('d.m.Y', $timestamp);
        } else {
            $timestamp = ' ';
        }
        return $timestamp;
    }
    private function formatDate($date)
    {
        return $this->timestampInDate(strtotime($date));
    }
    private function changeDate($date)
    {
        if($this->object->getEnddateCursive())
        {
            $date='<i>'.$date.'</i>';
        }
        if($this->object->getEnddateFat())
        {
            $date='<b>'.$date.'</b>';
        }


        $css="<style>
        .enddate
        {
            color: #".$this->object->getEnddateColor()." ;
        }
     </style>";


        $date=$css.'<span class="enddate">'.$date.'</span>';

        return $date;
    }
    private function getWorkStatus($edit_status,$id)
    {
        if($edit_status)
        {
            if($this->getWorkStatusMode())
            {
                return $this->getOkIconImage();
            }
            return $this->getOkIcon($id);
        }
        else
        {
            if($this->getWorkStatusMode())
            {
                return $this->getNotOkIconImage();
            }
            return $this->getNotOkIcon($id);
        }
    }
    private function getWorkStatusMode()
    {
       return $this->edit_acces;
    }

    private function getOkIconImage()
    {
        return "<img heigth='".$this->heigthAndWidth."' width='".$this->heigthAndWidth."' src=\"Customizing/global/plugins/Services/Repository/RepositoryObject/Todolists/templates/images/icon_ok.svg\">";
    }

    private function getNotOkIconImage()
    {
        $src_not_ok="Customizing/global/plugins/Services/Repository/RepositoryObject/Todolists/templates/images/icon_not_ok.svg";
        $src_ok="Customizing/global/plugins/Services/Repository/RepositoryObject/Todolists/templates/images/icon_ok.svg";
        $src_mouseover="Customizing/global/plugins/Services/Repository/RepositoryObject/Todolists/templates/images/icon_mouseover.svg";
        if(!$this->object->getShowEditStatusButton())return "<img heigth='".$this->heigthAndWidth."' width='".$this->heigthAndWidth."' src='".$src_not_ok."' onmouseover=\"src='". $src_mouseover."'\" onmouseout=\"src='".$src_not_ok."'\" />";
        else return "<img heigth='".$this->heigthAndWidth."' width='".$this->heigthAndWidth."' src='".$src_not_ok."'/>";
    }

    private function getOkIcon($id)
    {
        $image=$this->getOkIconImage();
        $link=$this->getLink(1,$id);
        return '<a id="green" href="' . $link . '"> '.$image.'</a>';
    }

    private function getNotOkIcon($id)
    {
        $image=$this->getNotOkIconImage();
        $link=$this->getLink(0,$id);
        return '<a id="red" href="' . $link . '"> '.$image.'</a>';
    }


    private function getLink($mode,$id)
    {
        $link = $_SERVER["REQUEST_URI"];
        $zeichen_eins = strpos($link, 'cmd');
        $zeichen_zwei = strpos($link, '&', $zeichen_eins);

        // 0 -> rot 1-> grün
        if($mode == 0 OR $mode == 1)
        {
            return substr_replace($link, 'changestatus&id=' . $id . '&status='.$mode, $zeichen_eins + 4, $zeichen_zwei - $zeichen_eins - 4);
        }

    }

}
?>