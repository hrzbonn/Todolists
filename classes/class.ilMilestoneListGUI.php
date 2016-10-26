<?php

include_once("class.ilMyTableGUI.php");


class ilMilestoneListGUI
{
    private $myTableGui;
    private $templatepath;
    private $templatename;
    private $parentCmd;
    private $pluginname;
    private $tableId;
    private $columnnames;
    private $columns;
    private $data;
    private $objid;
    
    function __construct($table_name,$objectid,$columnnames)
    {
        $this->setTemplatePath('/Customizing/global/plugins/Services/Repository/RepositoryObject/Todolists');
        $this->setTemplateName('table3');
        $this->setParentCmd('milestone');
        $this->setPluginName('Todolists');
        $this->setTableId('milestoneTable');
        $this->setObjectId($objectid);
        $this->setColumnNames($columnnames);

        $this->myTableGui = new ilMyTableGUI($this,$this->getParentCmd(),$this->getTemplatePath(),$this->getTemplateName(),$this->getPluginName(),$this->getTableId());
        $this->myTableGui->setFilterCommand("applyMilestoneListFilter"); 
        $this->myTableGui->setResetCommand("resetMilestoneListFilter");
        $this->myTableGui->setIsCalledByClass('ilObjTodolistsGUI');
        $this->myTableGui->setTableTitle($table_name);

        $this->getProgressActual();
        $this->setColumns();
        $this->myTableGui->initFilter();
        
    }
    
    function getHtml()
    {
        $this->setData();
        $this->myTableGui->setData($this->getData());
        $table=$this->myTableGui->getTableHTMLCODE();
        
        return $table;
    }

    function getMilestoneIDs()
    {
        global $ilDB;
        $sql_string="SELECT id FROM rep_robj_xtdo_milsto WHERE objectid = ". $this->getObjectId();
        $result = $ilDB->query($sql_string);
        $ids=array();
        while ($record = $ilDB->fetchAssoc($result))
        {
            array_push($ids,$record["id"]);
        }
        return $ids;
    }
    

    function getProgressActual()
    {
       global $ilDB;
       $ids=$this->getMilestoneIDs();
       
       foreach($ids as $id)
       {
        
            $sql_string="SELECT edit_status,tasks FROM rep_robj_xtdo_tasks WHERE milestone_id = ". $id;
            $result = $ilDB->query($sql_string);
            
            $fertig=0;
            $unfertig=0;
            
            while ($record = $ilDB->fetchAssoc($result))
            {
               if($record['edit_status'])
               {
                    $fertig++;
               }else
               {
                    $unfertig++;
               }               
            }
            $insgesamt=$fertig+$unfertig;
            if($insgesamt == 0)
            {
                $fertig=1;
                $insgesamt=1;
            }
            $prozent=round( ($fertig/$insgesamt)*100, 0, PHP_ROUND_HALF_UP);
            $ilDB->query("UPDATE rep_robj_xtdo_milsto SET progress=" . $ilDB->quote($prozent, "integer") . " WHERE id=" . $ilDB->quote($id, "integer"));      
       }
    }
    
    function applyFilter()
    {
        global $ilCtrl;
        $this->myTableGui->writeFilterToSession();        // writes filter to session
        $this->myTableGui->resetOffset();
        $ilCtrl->redirect($this, 'milestone');
    }

    function resetFilter()
    {
        global $ilCtrl;
        $this->myTableGui->resetOffset();                // sets record offest to 0 (first page)
        $this->myTableGui->resetFilter();
        $ilCtrl->redirect($this, 'milestone');
    }

    function TextFilter($parameter)
    {
        global $ilDB;

        if($this->myTableGui->filter[$parameter] != '')
        {
            $string=mysql_escape_string ( $this->myTableGui->filter[$parameter] );


            $parameter=str_replace('_'.$this->getTableId(),"",$parameter);


            return " AND ". $parameter ." LIKE ". $ilDB->quote('%'.$string.'%',"text");
        }else
        {
            return '';
        }
    }


    function specialFilter($parameter)
    {
        global $ilDB;
        if($this->myTableGui->filter[$parameter] != '')
        {
            global $ilUser;
            $integer=mysql_escape_string ( $this->myTableGui->filter[$parameter] );
            $parameter=str_replace('_'.$this->getTableId(),"",$parameter);
            return " AND ". $parameter ." = ". $ilDB->quote($ilUser->getUserIdByLogin($integer),"integer");
        }else
        {
            return '';
        }
    }


    function getStartSqlString()
    {
        global $ilDB;

        $sql_string="SELECT ";
        foreach($this->getColumns() as $column)
        {
            if($column['database_name']!="id" AND $column['database_name']!='')
            {
                $sql_string=$sql_string.$column['database_name'];
                $sql_string=$sql_string.',';
            }

        }
        $sql_string=$sql_string."id FROM rep_robj_xtdo_milsto WHERE";
        if(!$this->isCollectlist())$sql_string=$sql_string.  " objectid = ". $ilDB->quote($this->getObjectId(),"integer");

        return $sql_string;
    }

    function getIdsForCollectTheirMilestones()
    {
        global $ilDB;
        $sql_buffer="SELECT id FROM rep_robj_xtdo_data WHERE get_collect = ".$ilDB->quote($this->objid,"integer");
        $result = $ilDB->query($sql_buffer);
        $ids=array();
        while ($record = $ilDB->fetchAssoc($result))
        {
            array_push($ids,$record['id']);
        }
        array_push($ids,$this->objid);
        return $ids;
    }

    function addCollectlistString()
    {

        if($this->isCollectlist())
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

    function CollectListFilter($parameter,$sql_string)
    {

        if($this->myTableGui->filter[$parameter] != '')
        {
            $string=mysql_escape_string ( $this->myTableGui->filter[$parameter] );
            return $sql_string.$this->addIdsForCollectListAtString($this->CollectListFilterID($string));
        }else
        {
            return $sql_string.$this->addIdsForCollectListAtString($this->getIdsForCollectTheirMilestones());
        }
    }


    function addIdsForCollectListAtString($ids)
    {
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
    function CollectListFilterID($bezeichnung)
    {
        global $ilDB;
        $sql_buffer="SELECT id FROM rep_robj_xtdo_data WHERE namen LIKE ".$ilDB->quote('%'.$bezeichnung.'%',"text");
        $result = $ilDB->query($sql_buffer);
        $ids=array();
        while ($record = $ilDB->fetchAssoc($result))
        {
            array_push($ids,$record['id']);
        }
        array_push($ids,$this->objid);
        return $ids;
    }


    function setData()
    {
        global $ilDB;
        $sql_string=$this->getStartSqlString();


        if($this->isCollectlist())
        {
            $sql_string=$this->CollectListFilter('attechedto_'.$this->getTableId(),$sql_string);
        }
        else {$sql_string=$sql_string.$this->addCollectlistString();}
        foreach ($this->columns as $column)
        {
            if ($column['type'] == "text" AND $column['sort_and_filter'] != 'attechedto_'.$this->getTableId() AND $column['sort_and_filter'] != 'created_by_'.$this->getTableId() AND $column['sort_and_filter'] != 'updated_by_'.$this->getTableId() )
             {
                    $filter_title = $this->TextFilter($column['sort_and_filter']);
                    $sql_string = $sql_string . $filter_title;
             }
            if ($column['type'] == "text"  AND ($column['sort_and_filter'] == 'created_by_'.$this->getTableId() OR $column['sort_and_filter'] == 'updated_by_'.$this->getTableId()) )
            {
                $filter_title = $this->specialFilter($column['sort_and_filter']);
                $sql_string = $sql_string . $filter_title;
            }
        }
        
        $result = $ilDB->query($sql_string);
        $daten=array();
        while ($record = $ilDB->fetchAssoc($result))
        {
            $record["progress"]=$this->progressInPercentbar($record["progress"]);
            $record=$this->addData($record);
            array_push($daten,$record);
        }
        $this->data=$daten;
    }


    protected function getWorkListNameWithTaskId($task_id)
    {
        global $ilDB;

        $sql_buffer = "SELECT objectid FROM rep_robj_xtdo_milsto WHERE id = " . $ilDB->quote($task_id, "integer");
        $result = $ilDB->query($sql_buffer);
        while ($record = $ilDB->fetchAssoc($result)) {
            $object_id = $record["objectid"];
        }

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

    protected function getLinkRefId($obj_id)
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



    protected function getPath($id)
    {
        global $ilDB;
        $sql_buffer = "SELECT objectid FROM rep_robj_xtdo_milsto WHERE id = " . $ilDB->quote($id, "integer");
        $result = $ilDB->query($sql_buffer);
        while ($record = $ilDB->fetchAssoc($result)) {
            $object_id = $record["objectid"];
        }

        $sql_string="SELECT path FROM rep_robj_xtdo_data WHERE id = ".$ilDB->quote($object_id,"integer");
        $result = $ilDB->query($sql_string);
        while ($record = $ilDB->fetchAssoc($result))
        {
            $wert=$record["path"];
        }
        return $wert;
    }
    protected function getObjectIdWithTaskId($task_id)
    {
        global $ilDB;
        $sql_buffer = "SELECT objectid FROM rep_robj_xtdo_milsto WHERE id = " . $ilDB->quote($task_id, "integer");
        $result = $ilDB->query($sql_buffer);
        while ($record = $ilDB->fetchAssoc($result)) {
            $object_id = $record["objectid"];
        }
        return $object_id;
    }
    function setMoreCounter($a_value)
    {
        $this->moreCounter=$a_value;
    }
    function getMoreCounter()
    {
        return $this->moreCounter;
    }

    function addData($data)
    {
        $array=array();

        foreach($data as $key =>$value)
        {
            global $ilUser;
            switch ($key)
            {
                case "updated_by":
                    $array[$key] = $ilUser->getLoginByUserId($value);
                    break;
                case "created_by":
                    $array[$key] = $ilUser->getLoginByUserId($value);
                    break;
                case "milestone":
                    $array[$key]=$value;
                    if($this->isStartdateShown())$array["startdate"]=$this->getStartDate($data["id"]);
                    if($this->isEnddateShown())$array["enddate"]=$this->getEndDate($data["id"]);
                    break;
                case "description":
                    if(strlen($data[$key]) > 100)
                    {



                        $pos = strripos($data[$key]," ");
                        if($pos==false)$pos=100;
                        $string=substr($data[$key],0,100);
                        $pos = strripos($string," ");
                        if($pos==false)$pos=100;
                        $string=substr($data[$key],0,$pos+1);
                        $link_mehr='<a onClick="changeMehr(\'all_'.$this->getMoreCounter().'\',\'weniger_'.$this->getMoreCounter().'\')">...weiter lesen.</a>';
                        $link_weniger='<a onClick="changeWeniger(\'all_'.$this->getMoreCounter().'\',\'weniger_'.$this->getMoreCounter().'\')"> ...zuklappen.</a>';
                        $string=$string.$link_mehr;
                        $div="<div id = 'more_".$this->getMoreCounter()."'>
                                <div id = 'weniger_".$this->getMoreCounter()."'>".$string."</div>
                                <div id = 'all_".$this->getMoreCounter()."'>".$data[$key].$link_weniger."</div>
                              </div>";
                        $css="<style>
                            #all_".$this->getMoreCounter()."
                            {
                                display: none;
                            }
                        </style>";
                        $string=$css.$div;
                        $array[$key]=$string;
                        $counter=$this->getMoreCounter();
                        $this->setMoreCounter($counter+1);
                    }
                    else $array[$key]=$value;
                    break;
                case "progress":

                    if($this->isCollectlist())
                    {
                        $worklistname = $this->getPath($data['id']) . '->' . $this->getWorkListNameWithTaskId($data['id']);
                        $ref_id = $this->getLinkRefId($this->getObjectIdWithTaskId($data['id']));

                        $add_link = $_SERVER["REQUEST_URI"];
                        $add_link =substr($add_link,strpos($add_link,"cmdNode=")+8);

                        $link = "ilias.php?ref_id=" . $ref_id . "&cmd=milestone&cmdClass=ilobjtodolistsgui&cmdNode=".$add_link;
                        $link = "<a href='" . $link . "'>" . $worklistname . "<a>";
                        if($this->getWorkListNameWithTaskId($data['id']) != "" OR $this->getWorkListNameWithTaskId($data['id']) != NULL)
                        $array['attechedto_'.$this->getTableId()] = $link; else $array['attechedto_'.$this->getTableId()]="";
                    }
                    $array[$key]=$value;
                    $array["action"]=$this->addButton($data["id"]);
                    break;
                default:
                    $array[$key]=$value;
                    break;
            }
        }
        return $array;
    }

    function addButton($id)
    {
        global $lng,$ilCtrl;
        $ilCtrl->setParameterByClass('ilObjTodolistsGUI', "data_id",$id);
        $alist = new ilAdvancedSelectionListGUI();
        $alist->setId($id);
        $alist->setListTitle($lng->txt("actions"));
        $alist->addItem($lng->txt('edit'), 'edit_row_milestone',$ilCtrl->getLinkTargetByClass('ilObjTodolistsGUI', 'edit_row_milestone'));
        $alist->addItem($lng->txt('delete'), 'delete_row_milestone',$ilCtrl->getLinkTargetByClass('ilObjTodolistsGUI', 'delete_row_milestone'));
        return $alist->getHTML();
    }



    function getStartDate($id)
    {
        global $ilDB;

        $sql_string="SELECT startdate FROM rep_robj_xtdo_tasks WHERE milestone_id = ".$id;
        if(!$this->isCollectlist())$sql_string=$sql_string." AND objectid = ".$this->getObjectId();
        if($this->isCollectlist())$sql_string=$sql_string." AND ".$this->addCollectlistString();
        $result = $ilDB->query($sql_string);


        $counter = 0;
        while($record = $ilDB->fetchAssoc($result))
        {
            if($counter == 0)
            {
                $startdate_unix=strtotime($record["startdate"]);
                $counter ++;
            }
            if(strtotime($record["startdate"]) < $startdate_unix AND strtotime($record["startdate"])!=0)
            {
                $startdate_unix=strtotime($record["startdate"]);
            }
        }
        if($counter==0)
        {
            return "";
        }
        if($startdate_unix)
        return date("d.m.Y",$startdate_unix);
        else return "";
    }

    function getEndDate($id)
    {
        global $ilDB;

        $sql_string="SELECT enddate FROM rep_robj_xtdo_tasks WHERE milestone_id = ".$id;
        if(!$this->isCollectlist())$sql_string=$sql_string." AND objectid = ".$this->getObjectId();
        if($this->isCollectlist())$sql_string=$sql_string." AND ".$this->addCollectlistString();

        $result = $ilDB->query($sql_string);


        $counter = 0;
        while($record = $ilDB->fetchAssoc($result))
        {
            if($counter == 0)
            {
                $enddate_unix=strtotime($record["enddate"]);
                $counter ++;
            }
            if(strtotime($record["enddate"]) > $enddate_unix)
            {
                $enddate_unix=strtotime($record["enddate"]);
            }
        }
        if($counter==0)
        {
            return "";
        }
        if($enddate_unix) {
            if($this->getEnddateWarning() AND $enddate_unix < time())
            return $this->changeDate(date("d.m.Y", $enddate_unix));
            else
            return date("d.m.Y", $enddate_unix);
        }
        else return "";
    }

    protected function changeDate($date)
    {
        if($this->getEnddateCursive())
        {
            $date='<i>'.$date.'</i>';
        }
        if($this->getEnddateFat())
        {
            $date='<b>'.$date.'</b>';
        }


        $css="<style>
        .enddate
        {
            color: #".$this->getEnddateColor()." ;
        }
     </style>";


        $date=$css.'<span class="enddate">'.$date.'</span>';

        return $date;
    }
    protected function getEnddateWarning()
    {
        return $this->SqlSelectQueryOneValue('enddate_warning');
    }

    protected function getEnddateCursive()
    {
        return $this->SqlSelectQueryOneValue('enddate_cursive');
    }

    protected function getEnddateFat()
    {
        return $this->SqlSelectQueryOneValue('enddate_fat');
    }

    protected function getEnddateColor()
    {
        return $this->SqlSelectQueryOneValue('enddate_color');
    }

    function progressInPercentbar($progress)
    {
        $fertig_percent = round($progress, 0, PHP_ROUND_HALF_UP);
        
        $px_percent = (85 / 100) * $fertig_percent;
        $width_green = $px_percent . '%';
        $red_percent = 85 - $px_percent;
        $width_red = $red_percent . '%';
        $height = '20px';

        $image = '<img src="Customizing/global/plugins/Services/Repository/RepositoryObject/Todolists/templates/images/green_v1.png" height="' . $height . '" width="' . $width_green . '">';
        $image = $image . '<img src="Customizing/global/plugins/Services/Repository/RepositoryObject/Todolists/templates/images/red_v1.png" height="' . $height . '" width="' . $width_red . '">';
        $text =' ' . $fertig_percent . '% ';
        return $image . $text;
    }

    function getData()
    {
        return $this->data;
    }
    
    function setObjectId($value)
    {
        $this->objid=$value;
    }
    
    function getObjectId()
    {
        return $this->objid;
    }
    
    private function getColumns()
    {
        return $this->columns;
    }

    private function setColumns()
    {
        global $lng;
        $column_name_array=$this->getColumnNames();
        $columns=array();

        $add_at_description=$this->addAtDescriptionWidth();


        array_push($columns,$this->myTableGui->defineColumn($column_name_array[0],'milestone','milestone_'.$this->getTableId(),30,'text',true) );
        if($this->isStartdateShown())array_push($columns,$this->myTableGui->defineColumn($column_name_array[1],'','startdate_'.$this->getTableId(),10,'date',false));
        if($this->isEnddateShown())array_push($columns,$this->myTableGui->defineColumn($column_name_array[2],'','enddate_'.$this->getTableId(),10,'date',false));
        if($this->isDescriptionShown())array_push($columns,$this->myTableGui->defineColumn($column_name_array[3],'description','description_'.$this->getTableId(),10+$add_at_description,'text',true));
        if($this->isCreatedByShown())array_push($columns,$this->myTableGui->defineColumn($column_name_array[4],'created_by','created_by_'.$this->getTableId(),4,'text',true));
        if($this->isUpdatedByShown())array_push($columns,$this->myTableGui->defineColumn($column_name_array[5],'updated_by','updated_by_'.$this->getTableId(),3,'text',true));
        if($this->isCollectlist())array_push($columns,$this->myTableGui->defineColumn($column_name_array[6],'','attechedto_'.$this->getTableId(),10,'text',true));
        array_push($columns,$this->myTableGui->defineColumn($column_name_array[7],'progress','progress_'.$this->getTableId(),30,'text',true));
        array_push($columns,$this->myTableGui->defineColumn($lng->txt("action"),"","",30,'text',false));

        $this->columns=$columns;
        $this->myTableGui->setColumns($columns);
    }

    private function addAtDescriptionWidth()
    {
        $add_at_description=0;
        if(!$this->isStartdateShown())$add_at_description=$add_at_description+10;
        if(!$this->isCreatedByShown())$add_at_description=$add_at_description+4;
        if(!$this->isUpdatedByShown())$add_at_description=$add_at_description+3;
        if(!$this->isCollectlist())$add_at_description=$add_at_description+10;

        return $add_at_description;
    }

    protected function isDescriptionShown()
    {
        return $this->SqlSelectQueryOneValue('show_description');
    }
    protected function isEnddateShown()
    {
        return $this->SqlSelectQueryOneValue('show_enddate');
    }

    private function isCollectlist()
    {
        return $this->SqlSelectQueryOneValue('collectlist');
    }

    private function isCreatedByShown()
    {
        return $this->SqlSelectQueryOneValue('show_createdby');
    }

    private function isUpdatedByShown()
    {
        return $this->SqlSelectQueryOneValue('show_updatedby');
    }

    private function isStartdateShown()
    {
        return $this->SqlSelectQueryOneValue('show_startdate');
    }

    private function SqlSelectQueryOneValue($database_name)
    {
        global $ilDB;
        $sql_string="SELECT ".$database_name." FROM rep_robj_xtdo_data WHERE id = ".$ilDB->quote($this->objid,"integer");
        $result = $ilDB->query($sql_string);
        while ($record = $ilDB->fetchAssoc($result))
        {
            $wert=$record[$database_name];
        }
        return $wert;
    }

    function setColumnNames($value)
    {
        $this->columnnames=$value;
    }
    
    private function getColumnNames()
    {
        return $this->columnnames;
    }
    
    private function setTableId($value)
    {
        $this->tableId=$value;
    }

    private function getTableId()
    {
       return $this->tableId;
    }

    private function setPluginName($value)
    {
        $this->pluginname=$value;
    }

    private function getPluginName()
    {
        return $this->pluginname;
    }

    private function setParentCmd($value)
    {
        $this->parentCmd=$value;
    }

    private function getParentCmd()
    {
        return $this->parentCmd;
    }

    private function setTemplateName($value)
    {
        $this->templatename=$value;
    }

    private function getTemplateName()
    {
        return $this->templatename;
    }

    private function setTemplatePath($value)
    {
        $this->templatepath=$value;
    }

    private function getTemplatePath()
    {
        return $this->templatepath;
    }


}