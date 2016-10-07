<?php

include_once ('class.ilMyTableGUI.php');
include_once ('class.ilTaskListGUI.php');


class ilMilestoneTaskListGUI extends ilTaskListGUI
{

    private $mytablegui;
    private $columns;
    private $objid;
    private $milestoneid;
    private $table_id;
    
    function __construct($table_id,$table_title,$column_names,$optionen,$ref_id,$object_id)
    {
        global $ilAccess;
        $this->table_id=$table_id;
        $this->setObjectId($object_id);
        $this->setTasklistObjectId($object_id);
        $path='/Customizing/global/plugins/Services/Repository/RepositoryObject/Todolists';
        $this->mytablegui= new ilMyTableGUI($this,"showContent",$path,'table3','Todolists',$table_id);
        $this->mytablegui->setTableTitle($table_title);


        $this->mytablegui->setIsCalledByClass('ilObjTodolistsGUI');
        $this->mytablegui->setFilterCommand("applyMilestoneFilter");        // parent GUI class must implement this function
        $this->mytablegui->setResetCommand("resetMilestoneFilter");


        if ($ilAccess->checkAccess("edit_content", "",$ref_id ))
        {
            $this->mytablegui->addActionButton();
        }

        $columns = array();

        array_push($columns,$this->mytablegui->defineColumn($column_names[0],'tasks',$table_id.'_tasks',30,'text',true) );
        if($this->isShowStartdate())	array_push($columns,$this->mytablegui->defineColumn($column_names[1],'startdate',$table_id.'_startdate',10,'date',true));
        array_push($columns,$this->mytablegui->defineColumn($column_names[2],'enddate',$table_id.'_enddate',10,'date',true));
        array_push($columns,$this->mytablegui->defineColumn($column_names[3],'description',$table_id.'_description',30+$this->addAtDescriptionWidth(),'text',true));
        if($this->isShowCreatedby())	array_push($columns,$this->mytablegui->defineColumn($column_names[4],'created_by',$table_id.'_created_by',4,'text',true));
        if($this->isShowUpdatedby())	array_push($columns,$this->mytablegui->defineColumn($column_names[5],'updated_by',$table_id.'_updated_by',3,'text',true));
        array_push($columns,$this->mytablegui->defineColumn($column_names[7],'edit_status',$table_id.'_edit_status',3,'boolean',true,$optionen));
        if($this->isEditStatusButtonShown()) array_push($columns,$this->mytablegui->defineColumn($column_names[8],'id','',10,'text',false));


        $this->columns=$columns;
        $this->mytablegui->setColumns($columns);
        $this->setTasklistColumns($columns);

        if($this->isBeforeStartDateShown())
        {
            $ti = new ilCheckboxInputGUI($column_names[9], "extra_0");
            $this->mytablegui->setExtraFilter($ti);
        }



        $this->mytablegui->initFilter();


    }


    function applyFilter()
    {
        global $ilCtrl;
        $this->mytablegui->writeFilterToSession();        // writes filter to session
        $this->mytablegui->resetOffset();
        $ilCtrl->redirect($this, 'showContent');
    }

    function resetFilter()
    {
        global $ilCtrl;
        $this->mytablegui->resetOffset();                // sets record offest to 0 (first page)
        $this->mytablegui->resetFilter();
        $ilCtrl->redirect($this, 'showContent');
    }



    function BoolFilter($parameter)
    {
        global $ilDB;
        if(isset($parameter))
        {
            $wert= $this->mytablegui->filter[$parameter];
            $parameter=str_replace($this->table_id.'_',"",$parameter);
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

    public function resetOffset()
    {
        $this->mytablegui->resetOffset();
    }
    function CollectListFilter($parameter,$sql_string)
    {
        if($this->mytablegui->filter[$parameter] != '')
        {
            $string=mysql_escape_string ( $this->mytablegui->filter[$parameter] );
            $this->CollectListFilterID($string);

            return $sql_string.$this->addIdsForCollectListAtString($this->CollectListFilterID($string));
        }else
        {
            return $sql_string.$this->addIdsForCollectListAtString($this->getIdsForCollectTheirTasks());
        }
    }
    function TextFilter($parameter)
    {
        global $ilDB;

        if($this->mytablegui->filter[$parameter] != '')
        {
            $string=mysql_escape_string ( $this->mytablegui->filter[$parameter] );
            $parameter=str_replace($this->table_id.'_',"",$parameter);
            return " AND ". $parameter ." LIKE ". $ilDB->quote('%'.$string.'%',"text");
        }else
        {
            return '';
        }
    }
    function specialFilter($parameter)
    {
        global $ilDB;
        if($this->mytablegui->filter[$parameter] != '')
        {
            global $ilUser;
            $integer=mysql_escape_string ( $this->mytablegui->filter[$parameter] );
            $parameter=str_replace($this->table_id.'_',"",$parameter);
            return " AND ". $parameter ." = ". $ilDB->quote($ilUser->getUserIdByLogin($integer),"integer");
        }else
        {
            return '';
        }
    }

    function DateFilter($parameter)
    {
        global $ilDB;


        if(isset($this->mytablegui->filter[$parameter."_from"]) OR isset($this->mytablegui->filter[$parameter."_to"]))
        {
            $startdate=$this->mytablegui->filter[$parameter."_from"];
            $enddate=$this->mytablegui->filter[$parameter."_to"];


            $startdate = $startdate->getUnixTime();
            $enddate = $enddate->getUnixTime();


            if($startdate != 0 AND $enddate != 0)
            {
                $parameter=str_replace($this->table_id.'_',"",$parameter);
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
    
    
    

    function setMilestoneId($value)
    {
        $this->milestoneid=$value;
    }



    function setObjectId($value)
    {
        $this->objid=$value;
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
        $sql_string=$sql_string."id FROM rep_robj_xtdo_tasks WHERE objectid = ".$ilDB->quote($this->objid,"integer")." AND";
        $sql_string=$sql_string.  " milestone_id = ". $ilDB->quote($this->milestoneid,"integer");



        return $sql_string;
    }


    function getDataFromDb($filter = false)
    {

        $sql_string=$this->getSqlString();
        if($this->isCollectlist())$sql_string=substr($sql_string,0,strpos($sql_string,"WHERE")+5).' ';
        if($this->isCollectlist())$sql_string=$sql_string."milestone_id = ".$this->milestoneid." AND ";

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

                if ($column['type'] == "text" AND $column['sort_and_filter'] != 'attechedto' AND $column['sort_and_filter'] != $this->table_id.'_created_by' AND $column['sort_and_filter'] != $this->table_id.'_updated_by' ) {
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
                if ($column['type'] == "text"  AND ($column['sort_and_filter'] == $this->table_id.'_created_by' OR $column['sort_and_filter'] == $this->table_id.'_updated_by') )
                {
                    $filter_title = $this->specialFilter($column['sort_and_filter']);
                    $sql_string = $sql_string . $filter_title;
                }
                $i++;
            }
        }
        $this->setDBdata($sql_string);

    }





    function setDBdata($sql_string)
    {
        global $ilDB;
        $allData=array();
        if($sql_string != '') {
            
            $result = $ilDB->query($sql_string);
            while ($record = $ilDB->fetchAssoc($result)) {


                $enddate_time_stamp = strtotime($record['enddate']);

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
            $this->mytablegui->setData($allData);
        }
    }


    function formatDate($date)
    {
        return $this->timestampInDate(strtotime($date));
    }


    protected function getDataSorted($record,$edit_status)
    {
        global $ilUser;
        $sorted_record = array();
        foreach ($record as $key => $value) {
            if ($key == "edit_status") {
                $sorted_record[$key] = $value;
            }
            if ($key == "id") {
                if ($this->isEditStatusButtonShown()) $sorted_record['fertig'] = $this->setChangeButton($value, $edit_status);
                $sorted_record[$key] = $value;
            }
            if($key == "created_by" OR $key == "updated_by")
            {
                $sorted_record[$key] = $ilUser->getLoginByUserId($value);
            }
            if ($key != "id" AND $key != "edit_status" AND $key != "created_by" AND $key != "updated_by") {
                $sorted_record[$key] = $value;
            }
        }
        return $sorted_record;
    }

    
    function getHTML()
    {
        return $this->mytablegui->getTableHTMLCODE();
    }




    
}