
<?php

include_once("class.ilMyTableGUI.php");
include_once ('class.ilAdvancedButtonGUI.php');
include_once("./Services/Form/classes/class.ilTextInputGUI.php");
include_once ("./Services/Form/classes/class.ilCheckboxInputGUI.php");
include_once ("./Services/Form/classes/class.ilCheckboxGroupInputGUI.php");
include_once ("./Services/Form/classes/class.ilSelectInputGUI.php");
include_once ("./Services/Form/classes/class.ilDateDurationInputGUI.php");


class ilTaskListGUI
{
    private $table_gui;
    private $columns;
    private $objid;
    private $limit;
    private $taskListRefId;




    function __construct($column_name_array,$objectid,$ref_id,$optionen,$listnname)
    {
        global $ilAccess;
        $this->limit=10;
        $this->objid=$objectid;
        $this->taskListRefId=$ref_id;
        $this->table_gui = new ilMyTableGUI($this, "showContent",'/Customizing/global/plugins/Services/Repository/RepositoryObject/Todolists','table3','Todolists','Tasklist');
        $this->table_gui->setTableTitle($listnname);

        if ($ilAccess->checkAccess("edit_content", "",$ref_id ))
        {
            $this->table_gui->addActionButton();
        }

        $this->table_gui->setIsCalledByClass('ilObjTodolistsGUI');
        $this->table_gui->setFilterCommand("applyFilter");        // parent GUI class must implement this function
        $this->table_gui->setResetCommand("resetFilter");

        $columns = array();
        $add_at_description=$this->addAtDescriptionWidth();
        

        array_push($columns,$this->table_gui->defineColumn($column_name_array[0],'tasks','tasks',30,'text',true) );
        if($this->isShowStartdate())	array_push($columns,$this->table_gui->defineColumn($column_name_array[1],'startdate','startdate',10,'date',true));
        array_push($columns,$this->table_gui->defineColumn($column_name_array[2],'enddate','enddate',10,'date',true));
        array_push($columns,$this->table_gui->defineColumn($column_name_array[3],'description','description',30+$add_at_description,'text',true));
        if($this->isShowCreatedby())	array_push($columns,$this->table_gui->defineColumn($column_name_array[4],'created_by','created_by',4,'text',true));
        if($this->isShowUpdatedby())	array_push($columns,$this->table_gui->defineColumn($column_name_array[5],'updated_by','updated_by',3,'text',true));
        if($this->isCollectlist())		array_push($columns,$this->table_gui->defineColumn($column_name_array[6],'','attechedto',10,'text',true));
        array_push($columns,$this->table_gui->defineColumn($column_name_array[7],'edit_status','edit_status',3,'boolean',true,$optionen));
        if($this->isEditStatusButtonShown()) array_push($columns,$this->table_gui->defineColumn($column_name_array[8],'id','',10,'text',false));

        $this->columns=$columns;
        $this->table_gui->setColumns($columns);

        
        

        
        if($this->isBeforeStartDateShown())
        {
            $ti = new ilCheckboxInputGUI($column_name_array[9], "extra_0");
            $this->table_gui->setExtraFilter($ti);
        }
        $this->table_gui->initFilter();

    }
    
    public function resetOffset()
    {
        $this->table_gui->resetOffset();
    }


    protected function addAtDescriptionWidth()
    {
        $add_at_description=0;
        if(!$this->isShowStartdate())$add_at_description=$add_at_description+10;
        if(!$this->isShowCreatedby())$add_at_description=$add_at_description+4;
        if(!$this->isShowUpdatedby())$add_at_description=$add_at_description+3;
        if(!$this->isCollectlist())$add_at_description=$add_at_description+10;

        return $add_at_description;
    }

    protected function SqlSelectQueryOneValue($database_name)
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

    
    function setTasklistObjectId($value)
    {
        $this->objid=$value;
    }
    function setTasklistColumns($value)
    {
        $this->columns=$value;
    }
    
    protected function isBeforeStartDateShown()
    {
        return $this->SqlSelectQueryOneValue('before_startdate_shown');
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

    protected function isCollectlist()
    {
        return $this->SqlSelectQueryOneValue('collectlist');
    }

    protected function isEditStatusButtonShown()
    {
        return $this->SqlSelectQueryOneValue('show_edit_status_button');
    }

    protected function isShowStartdate()
    {
        return $this->SqlSelectQueryOneValue('show_startdate');
    }

    protected function isShowUpdatedby()
    {
        return $this->SqlSelectQueryOneValue('show_updatedby');
    }

    protected function isShowCreatedby()
    {
        return $this->SqlSelectQueryOneValue('show_createdby');
    }

    protected function isEditStatusPermission()
    {
        return $this->SqlSelectQueryOneValue('edit_status_permission');
    }

    protected function notAtStartdateFilter()
    {
        if(!$this->table_gui->filter["extra_0"])
        {
            return $this->notAtStartdate();
        }else
        {
            return '';
        }
    }

    function applyFilter()
    {
        global $ilCtrl;
        $this->table_gui->writeFilterToSession();        // writes filter to session
        $this->table_gui->resetOffset();
        $ilCtrl->redirect($this, 'showContent');
    }

    function resetFilter()
    {
        global $ilCtrl;
        $this->table_gui->resetOffset();                // sets record offest to 0 (first page)
        $this->table_gui->resetFilter();
        $ilCtrl->redirect($this, 'showContent');
    }

    protected function setChangeButton($id,$status)
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

    protected function getWorkListNameWithTaskId($task_id)
    {
        global $ilDB;

            $sql_buffer = "SELECT objectid FROM rep_robj_xtdo_tasks WHERE id = " . $ilDB->quote($task_id, "integer");
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

    protected function timestampInDate($timestamp)
    {
        if ($timestamp != 0) {
            $timestamp = date('d.m.Y', $timestamp);
        } else {
            $timestamp = ' ';
        }
        return $timestamp;
    }

    protected function getOkIconImage()
    {
        return "<img src=\"Customizing/global/plugins/Services/Repository/RepositoryObject/Todolists/templates/images/icon_ok.svg\">";
    }

    protected function getNotOkIconImage()
    {
        return "<img src=\"Customizing/global/plugins/Services/Repository/RepositoryObject/Todolists/templates/images/icon_not_ok.svg\">";
    }

    protected function getOkIcon($id)
    {
        $image=$this->getOkIconImage();
        $link=$this->getLink(1,$id);
        return '<a id="green" href="' . $link . '"> '.$image.'</a>';
    }

    protected function getNotOkIcon($id)
    {
        $image=$this->getNotOkIconImage();
        $link=$this->getLink(0,$id);
        return '<a id="red" href="' . $link . '"> '.$image.'</a>';
    }

    protected function getLink($mode,$id)
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

    protected function getWorkStatus($edit_status,$id)
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

    protected function getWorkStatusMode()
    {
        global $ilAccess;
        if ( ($this->isEditStatusPermission() AND !$ilAccess->checkAccess("edit_content", "", $this->taskListRefId)) OR $this->isEditStatusButtonShown() )
        {
             return true;   
        }
        return false;
    }

    protected function getPath($id)
    {
        global $ilDB;
        $sql_buffer = "SELECT objectid FROM rep_robj_xtdo_tasks WHERE id = " . $ilDB->quote($id, "integer");
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

    protected function getObjectIdWithTaskId($task_id)
    {
        global $ilDB;
        $sql_buffer = "SELECT objectid FROM rep_robj_xtdo_tasks WHERE id = " . $ilDB->quote($task_id, "integer");
        $result = $ilDB->query($sql_buffer);
        while ($record = $ilDB->fetchAssoc($result)) {
            $object_id = $record["objectid"];
        }
        return $object_id;
    }


    protected function getDataSorted($record,$edit_status)
    {
        $sorted_record = array();
        foreach ($record as $key => $value) {
            if ($key == "edit_status") {

                if ($this->isCollectlist())
                {

                    $worklistname=$this->getPath($record['id']).'->'.$this->getWorkListNameWithTaskId($record['id']);
                    $ref_id=$this->getLinkRefId($this->getObjectIdWithTaskId($record['id']));


                    $add_link = $_SERVER["REQUEST_URI"];
                    $add_link =substr($add_link,strpos($add_link,"cmdNode=")+8);


                    $link="ilias.php?ref_id=".$ref_id."&cmd=showContent&cmdClass=ilobjtodolistsgui&cmdNode=".$add_link;
                    if($this->getWorkListNameWithTaskId($record['id']) != "" OR $this->getWorkListNameWithTaskId($record['id']) != NULL)
                        $sorted_record['liste'] = "<a href='".$link."'>".$worklistname."<a>";
                    else $sorted_record["liste"]="";
                }
                $sorted_record[$key] = $value;
            }
            if ($key == "id") {
                if ($this->isEditStatusButtonShown()) $sorted_record['fertig'] = $this->setChangeButton($value, $edit_status);
                $sorted_record[$key] = $value;
            }
            if($key == "created_by" OR $key == "updated_by")
            {
                global $ilUser;
                $sorted_record[$key] = $ilUser->getLoginByUserId($value);
            }
            if ($key != "id" AND $key != "edit_status" AND $key != "created_by" AND $key != "updated_by") {
                $sorted_record[$key] = $value;
            }
        }
        return $sorted_record;
    }

    function setDBdata($sql_string)
    {
        global $ilDB;
        $allData=array();

        if($sql_string != '') {


            $result = $ilDB->query($sql_string);
            while ($record = $ilDB->fetchAssoc($result)) {


                $enddate_time_stamp = strtotime( $record['enddate'] );

                $record['enddate'] = $this->formatDate($record['enddate']);

                if (isset($record['startdate'])) {
                    $record['startdate'] = $this->formatDate($record['startdate']);
                }

                if ($this->getEnddateWarning() AND $enddate_time_stamp < time()) {
                    $record['enddate'] = $this->changeDate($record['enddate']);
                }

                $edit_status = $record['edit_status'];
                $record['edit_status'] = $this->getWorkStatus($record['edit_status'], $record['id']);


                $sorted_record=$this->getDataSorted($record,$edit_status);
                array_push($allData, $sorted_record);
             }
        $this->setDataFromDb($allData);
        }
    }

    function formatDate($date)
    {
        return $this->timestampInDate(strtotime($date));
    }

    function getSqlString()
    {
        global $ilDB;

        $sql_string="SELECT ";
        foreach($this->columns as $column)
        {
            if($column['database_name']!="id" AND $column['database_name']!='')
            {
                $sql_string=$sql_string.$column['database_name'];
                $sql_string=$sql_string.',';
            }

        }
        $sql_string=$sql_string."id FROM rep_robj_xtdo_tasks WHERE milestone_id = 0 AND";
        $sql_string=$sql_string.  " objectid = ". $ilDB->quote($this->objid,"integer");

        return $sql_string;
    }
    
    function getIdsForCollectTheirTasks()
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
    
    function getDataFromDb($filter = false)
    {

        $sql_string=$this->getSqlString();
        if($this->isCollectlist())$sql_string=substr($sql_string,0,strpos($sql_string,"WHERE")+5).' ';
        if($this->isCollectlist())$sql_string=$sql_string."milestone_id = 0 AND ";

        if(!$filter)
        {
            if($this->isCollectlist())$sql_string=$sql_string.$this->addIdsForCollectListAtString($this->getIdsForCollectTheirTasks());
            $sql_string=$sql_string.$this->noFilterAddString();
            $sql_string=$sql_string.$this->notAtStartdate();
        }else
        {
            $i=0;
            if($this->isCollectlist())$sql_string=$this->CollectListFilter('attechedto',$sql_string);
            $sql_string=$sql_string.$this->notAtStartdateFilter();
            foreach($this->columns as $column)
            {

                    if ($column['type'] == "text" AND $column['sort_and_filter'] != 'attechedto' AND $column['sort_and_filter'] != 'created_by' AND $column['sort_and_filter'] != 'updated_by' ) {
                        $filter_title = $this->TextFilter($column['sort_and_filter']);
                        $sql_string = $sql_string . $filter_title;
                    }

                    if ($column['type'] == "boolean") {
                        $filter_title = $this->BoolFilter($column['sort_and_filter']);
                        $sql_string = $sql_string . $filter_title;
                    }
                    if ($column['type'] == "date") {
                        $filter_title = $this->DateFilter($column['sort_and_filter']);
                        $sql_string = $sql_string . $filter_title;
                    }
                if ($column['type'] == "text"  AND ($column['sort_and_filter'] == 'created_by' OR $column['sort_and_filter'] == 'updated_by') )
                {
                    $filter_title = $this->specialFilter($column['sort_and_filter']);
                    $sql_string = $sql_string . $filter_title;
                }
                $i++;
            }
        }
        $this->setDBdata($sql_string);

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
    
    function noFilterAddString()
    {
        global $ilDB;
        $sql_string="SELECT are_finished_shown FROM rep_robj_xtdo_data WHERE id = ".$ilDB->quote($this->objid,"integer");
        $result = $ilDB->query($sql_string);
        while ($record = $ilDB->fetchAssoc($result))
        {
            $bool_buffer=$record['are_finished_shown'];
        }

        if($bool_buffer)
        {
           return ' AND edit_status='.$ilDB->quote(0,"integer");
        }else
        {
            return '';
        }
    }
    function notAtStartdate()
    {
        global $ilDB;
        $sql_string="SELECT before_startdate_shown FROM rep_robj_xtdo_data WHERE id = ".$ilDB->quote($this->objid,"integer");
        $result = $ilDB->query($sql_string);
        while ($record = $ilDB->fetchAssoc($result))
        {
            $bool_buffer=$record['before_startdate_shown'];
        }
        if($bool_buffer)
        {
            return ' AND startdate <='.$ilDB->quote(time(),"integer");
        }else
        {
            return "";
        }
    }
    

    function BoolFilter($parameter)
    {
        global $ilDB;
        if(isset($parameter))
        {
            $wert= $this->table_gui->filter[$parameter];
            if($wert == 0)
            {
                return '';
            }
            if($wert == 1)
            {
                return " AND ". $parameter ." = ". $ilDB->quote(1,"integer");
            }
            if($wert == 2)
            {
                return " AND " . $parameter . " = " . $ilDB->quote(0, "integer");
            }
        }else
        {
            return '';
        }
    }


    function CollectListFilter($parameter,$sql_string)
    {

        if($this->table_gui->filter[$parameter] != '')
        {
            $string=mysql_escape_string ( $this->table_gui->filter[$parameter] );

            return $sql_string.$this->addIdsForCollectListAtString($this->CollectListFilterID($string));
        }else
        {
            return $sql_string.$this->addIdsForCollectListAtString($this->getIdsForCollectTheirTasks());
        }
    }
    function TextFilter($parameter)
    {
        global $ilDB;
        if($this->table_gui->filter[$parameter] != '')
        {
            $string=mysql_escape_string ( $this->table_gui->filter[$parameter] );
            return " AND ". $parameter ." LIKE ". $ilDB->quote('%'.$string.'%',"text");
        }else
        {
            return '';
        }
    }
    function specialFilter($parameter)
    {
        global $ilDB;
        if($this->table_gui->filter[$parameter] != '')
        {
            global $ilUser;
            $integer=mysql_escape_string ( $this->table_gui->filter[$parameter] );
            return " AND ". $parameter ." = ". $ilDB->quote($ilUser->getUserIdByLogin($integer),"integer");
        }else
        {
            return '';
        }
    }


    function DateFilter($parameter)
    {
        global $ilDB;


        if(isset($this->table_gui->filter[$parameter."_from"]) OR isset($this->mytablegui->filter[$parameter."_to"]))
        {
            $startdate=$this->table_gui->filter[$parameter."_from"];
            $enddate=$this->table_gui->filter[$parameter."_to"];


            $startdate = $startdate->getUnixTime();
            $enddate = $enddate->getUnixTime();


            if($startdate != 0 AND $enddate != 0)
            {
                $startdate = date("Y-m-d",$startdate);
                $enddate = date("Y-m-d",$enddate);
                return " AND (" . $parameter . " BETWEEN " . $ilDB->quote($startdate, "text")." AND ". $ilDB->quote($enddate, "text").')';
            }else
            {
                return '';
            }


        }
        else
        {
            return '';
        }
    }

    function setLimit($limit)
    {
        $this->table_gui->setMyLimit($limit);
    }


    protected function setDataFromDb($setData)
    {
        $this->table_gui->setmyData($setData);
    }

    function getTable()
    {
        return $this->table_gui->getTableHTMLCODE();
    }
}
?>



