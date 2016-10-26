
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
    private $heigthAndWidth;




    function __construct($column_name_array,$objectid,$ref_id,$optionen,$listnname)
    {
        global $ilAccess;
        $this->heigthAndWidth="100%";
        $this->limit=10;
        $this->objid=$objectid;
        $this->taskListRefId=$ref_id;
        $this->table_gui = new ilMyTableGUI($this, "showContent",'/Customizing/global/plugins/Services/Repository/RepositoryObject/Todolists','table3','Todolists','Tasklist');
        $this->table_gui->setTableTitle($listnname);
        $this->setMoreCounter(0);

        if ($ilAccess->checkAccess("edit_content", "",$ref_id ))
        {
            $this->table_gui->addActionButton();
        }

        $this->table_gui->setIsCalledByClass('ilObjTodolistsGUI');
        $this->table_gui->setFilterCommand("applyFilter");        // parent GUI class must implement this function
        $this->table_gui->setResetCommand("resetFilter");

        $columns = array();
        $width=$this->getWidth();
        $description_width=$this->getDescriptionWidth();
        $width=$this->correctWidth($width,$description_width);

        if(!$this->isStatusPosition())   array_push($columns,$this->table_gui->defineColumn($column_name_array[7],'edit_status','edit_status',3,'boolean',true,$optionen));
        if($this->isEditStatusButtonShown() AND !$this->isStatusPosition()) array_push($columns,$this->table_gui->defineColumn($column_name_array[8],'id','',$width,'text',false));
        array_push($columns,$this->table_gui->defineColumn($column_name_array[0],'tasks','tasks',$width,'text',true) );
        if($this->isShowStartdate())	array_push($columns,$this->table_gui->defineColumn($column_name_array[1],'startdate','startdate',$width,'date',true));
        if($this->isEnddateShown())array_push($columns,$this->table_gui->defineColumn($column_name_array[2],'enddate','enddate',$width,'date',true));
        if($this->isDescriptionShown())array_push($columns,$this->table_gui->defineColumn($column_name_array[3],'description','description',$description_width,'text',true));
        if($this->isShowCreatedby())	array_push($columns,$this->table_gui->defineColumn($column_name_array[4],'created_by','created_by',$width,'text',true));
        if($this->isShowUpdatedby())	array_push($columns,$this->table_gui->defineColumn($column_name_array[5],'updated_by','updated_by',$width,'text',true));
        if($this->isCollectlist())		array_push($columns,$this->table_gui->defineColumn($column_name_array[6],'','attechedto',$width,'text',true));
        if($this->isStatusPosition())   array_push($columns,$this->table_gui->defineColumn($column_name_array[7],'edit_status','edit_status',$width,'boolean',true,$optionen));
        if($this->isEditStatusButtonShown() AND $this->isStatusPosition()) array_push($columns,$this->table_gui->defineColumn($column_name_array[8],'id','',$width,'text',false));

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

    protected function correctWidth($width,$description_width)
    {
        if($description_width==0)return $width;

        $columnnumber=1;
        if($this->isShowStartdate())$columnnumber++;
        if($this->isShowCreatedby())$columnnumber++;
        if($this->isShowUpdatedby())$columnnumber++;
        if($this->isCollectlist())$columnnumber++;
        if($this->isEditStatusButtonShown())$columnnumber++;
        if($this->isEnddateShown())$columnnumber++;

        return (97-$description_width)/$columnnumber;

    }

    protected function getDescriptionWidth()
    {
        if(!$this->isDescriptionShown())return 0;
        return 97/2;
    }

    protected function getWidth()
    {
        $columnnumber=1;
        if($this->isShowStartdate())$columnnumber++;
        if($this->isShowCreatedby())$columnnumber++;
        if($this->isShowUpdatedby())$columnnumber++;
        if($this->isCollectlist())$columnnumber++;
        if($this->isEditStatusButtonShown())$columnnumber++;
        if($this->isEnddateShown())$columnnumber++;
        if($this->isDescriptionShown())$columnnumber++;

        return 97/$columnnumber;
    }
    
    public function setHeigthAndWidth($heigthAndWidth)
    {
        $this->heigthAndWidth=$heigthAndWidth;    
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

    protected function isDescriptionShown()
    {
        return $this->SqlSelectQueryOneValue('show_description');
    }
    protected function isEnddateShown()
    {
        return $this->SqlSelectQueryOneValue('show_enddate');
    }

    protected function isStatusPosition()
    {
        return $this->SqlSelectQueryOneValue('status_position');
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
        return "<img heigth='".$this->heigthAndWidth."' width='".$this->heigthAndWidth."' src=\"Customizing/global/plugins/Services/Repository/RepositoryObject/Todolists/templates/images/icon_ok.svg\">";
    }

    protected function getNotOkIconImage()
    {
        $src_not_ok="Customizing/global/plugins/Services/Repository/RepositoryObject/Todolists/templates/images/icon_not_ok.svg";
        $src_ok="Customizing/global/plugins/Services/Repository/RepositoryObject/Todolists/templates/images/icon_ok.svg";
        $src_mouseover="Customizing/global/plugins/Services/Repository/RepositoryObject/Todolists/templates/images/icon_mouseover.svg";
        if(!$this->isEditStatusButtonShown())return "<img heigth='".$this->heigthAndWidth."' width='".$this->heigthAndWidth."' src='".$src_not_ok."' onmouseover=\"src='". $src_mouseover."'\" onmouseout=\"src='".$src_not_ok."'\" />";
        else return "<img heigth='".$this->heigthAndWidth."' width='".$this->heigthAndWidth."' src='".$src_not_ok."'/>";
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
        global $ilUser;
        $sorted_record = array();
        foreach ($record as $key => $value) {

            switch ($key)
            {
                case "tasks":
                    if(!$this->isStatusPosition() AND $this->isEditStatusButtonShown())$sorted_record["fertig"]=$record["fertig"];
                    $sorted_record[$key] = $value;
                    break;
                case "edit_status":
                    if($this->isStatusPosition() AND $this->isCollectlist())$sorted_record["attechedto"]=$record["attechedto"];
                    $sorted_record[$key] = $value;
                    break;
                default:
                    $sorted_record[$key] = $value;
            }

        }
        return $sorted_record;
    }

    function getWorklistLinkForCollectlist($id)
    {
        $worklistname=$this->getPath($id).'->'.$this->getWorkListNameWithTaskId($id);
        $ref_id=$this->getLinkRefId($this->getObjectIdWithTaskId($id));


        $add_link = $_SERVER["REQUEST_URI"];
        $add_link =substr($add_link,strpos($add_link,"cmdNode=")+8);


        $link="ilias.php?ref_id=".$ref_id."&cmd=showContent&cmdClass=ilobjtodolistsgui&cmdNode=".$add_link;
        if($this->getWorkListNameWithTaskId($id) != "" OR $this->getWorkListNameWithTaskId($id) != NULL)
            return "<a href='".$link."'>".$worklistname."<a>";
        else return "";
    }

    function setMoreCounter($a_value)
    {
        $this->moreCounter=$a_value;
    }
    function getMoreCounter()
    {
        return $this->moreCounter;
    }
    function preDataSorted($record,$edit_status,$table_id)
    {
        global $ilUser;
        $sorted_record = array();
        foreach ($record as $key => $value) {

            switch ($key)
            {
                case "created_by":
                    $sorted_record[$key] = $ilUser->getLoginByUserId($value);
                    break;
                case "updated_by":
                    $sorted_record[$key] = $ilUser->getLoginByUserId($value);
                    break;
                case "id":
                    $sorted_record[$key] = $value;
                    if ($this->isEditStatusButtonShown()) $sorted_record['fertig'] = $this->setChangeButton($value, $edit_status);
                    if ($this->isCollectlist())$sorted_record["attechedto"]=$this->getWorklistLinkForCollectlist($value);
                    break;
                case "description":
                    /*if(strlen($record[$key]) > 100 AND false)
                    {
                        $more="<a>...weiter lesen.</a>";



                        $pos = strripos($record[$key]," ");
                        if($pos==false)$pos=100;
                        $string=substr($record[$key],0,100);
                        $pos = strripos($string," ");
                        if($pos==false)$pos=100;
                        $string=substr($record[$key],0,$pos+1);
                        $string=$string.$more;

                        $div="<div class = 'more'>
                                <div class = 'weniger'>".$string."</div>
                                <div class = 'all'>".$record[$key]."</div>
                              </div>";
                        $css="<style>
                            .more .all
                            {
                                display: none;
                            }
                            .more:hover .all
                            {
                                display: inline;
                            }
                            .more:hover .weniger
                            {
                                display: none;
                            }
                        </style>";

                        $string=$css.$div;
                        $sorted_record[$key]=$string;
                    }*/
                    if(strlen($record[$key]) > 100)
                    {



                        $pos = strripos($record[$key]," ");
                        if($pos==false)$pos=100;
                        $string=substr($record[$key],0,100);
                        $pos = strripos($string," ");
                        if($pos==false)$pos=100;
                        $string=substr($record[$key],0,$pos+1);
                        $link_mehr='<a onClick="changeMehr(\'all_'.$table_id.$this->getMoreCounter().'\',\'weniger_'.$table_id.$this->getMoreCounter().'\')">...weiter lesen.</a>';
                        $link_weniger='<a onClick="changeWeniger(\'all_'.$table_id.$this->getMoreCounter().'\',\'weniger_'.$table_id.$this->getMoreCounter().'\')"> ...zuklappen.</a>';
                        $string=$string.$link_mehr;
                        $div="<div id = 'more_".$table_id.$this->getMoreCounter()."'>
                                <div id = 'weniger_".$table_id.$this->getMoreCounter()."'>".$string."</div>
                                <div id = 'all_".$table_id.$this->getMoreCounter()."'>".$record[$key].$link_weniger."</div>
                              </div>";
                        $css="<style>
                            #all_".$table_id.$this->getMoreCounter()."
                            {
                                display: none;
                            }
                        </style>";
                        $string=$css.$div;
                        $sorted_record[$key]=$string;
                        $counter=$this->getMoreCounter();
                        $this->setMoreCounter($counter+1);
                    }
                    else $sorted_record[$key] = $value;
                    break;
                default:
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


                if (isset($record['enddate']))
                {
                    $enddate_time_stamp = strtotime( $record['enddate'] );
                    $record['enddate'] = $this->formatDate($record['enddate']);
                    if ($this->getEnddateWarning() AND $enddate_time_stamp < time()) {
                        $record['enddate'] = $this->changeDate($record['enddate']);
                    }
                }

                if (isset($record['startdate'])) {
                    $record['startdate'] = $this->formatDate($record['startdate']);
                }



                $edit_status = $record['edit_status'];
                $record['edit_status'] = $this->getWorkStatus($record['edit_status'], $record['id']);

                $record=$this->preDataSorted($record,$edit_status,"Tasklist");
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



