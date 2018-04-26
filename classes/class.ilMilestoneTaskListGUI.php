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
    private $object;
    private $refid;
    private $direction_array;
    private $column_names;
    private $optionen;
    
    function __construct($table_id,$table_title,$column_names,$optionen,$ref_id,$object_id,$object)
    {
        global $ilAccess;
        $this->optionen=$optionen;
        $this->table_id=$table_id;
        $this->column_names=$column_names;
        $this->refid=$ref_id;
        $this->setObjectId($object_id);
        $this->setTasklistObjectId($object_id);
        $this->object=$object;
        $path='/Customizing/global/plugins/Services/Repository/RepositoryObject/Todolists';
        $this->mytablegui= new ilMyTableGUI($this,"showContent",$path,'table3','Todolists',$table_id);
        $this->mytablegui->setTableTitle($table_title);
        $this->setHeigthAndWidth("100%");

        $this->mytablegui->setIsCalledByClass('ilObjTodolistsGUI');
        $this->mytablegui->setFilterCommand("applyMilestoneFilter");        // parent GUI class must implement this function
        $this->mytablegui->setResetCommand("resetMilestoneFilter");

        if ($ilAccess->checkAccess("edit_content", "",$ref_id ))
        {
            $this->mytablegui->addOwnActionButton();
        }


        if($this->object->getBeforeStartdateShown())
        {
            $ti = new ilCheckboxInputGUI($column_names[9], "extra_0");
            $this->mytablegui->setExtraFilter($ti);
        }
        $this->setColumns();
        $this->mytablegui->setColumns($this->columns);
        $this->setTasklistColumns($this->columns);
        $this->mytablegui->initFilter();


    }
    function setColumns()
    {

        $this->setObject($this->object);
        $width=$this->getWidth();
        $description_width=$this->getDescriptionWidth();
        $width=$this->correctWidth($width,$description_width);


        $columns = array();
        $this->direction_array=array();
        if(!$this->object->getStatusPosition())
        {
            array_push($columns,$this->mytablegui->defineColumn($this->column_names[7],'edit_status',$this->table_id.'_edit_status',3,'boolean',true,$this->optionen));
            array_push($this->direction_array,"edit_status");
        }
        if($this->object->getShowEditStatusButton() AND !$this->object->getStatusPosition())
        {
            array_push($columns,$this->mytablegui->defineColumn($this->column_names[8],'id','',$width,'text',false));
            array_push($this->direction_array,"button");
        }
        array_push($columns,$this->mytablegui->defineColumn($this->column_names[0],'tasks',$this->table_id.'_tasks',$width,'text',true) );
        array_push($this->direction_array,"tasks");
        if($this->object->getShowStartDate())
        {
            array_push($columns,$this->mytablegui->defineColumn($this->column_names[1],'startdate',$this->table_id.'_startdate',$width,'date',true));
            array_push($this->direction_array,"startdate");
        }
        if($this->object->getShowEnddate())
        {
            array_push($columns,$this->mytablegui->defineColumn($this->column_names[2],'enddate',$this->table_id.'_enddate',$width,'date',true));
            array_push($this->direction_array,"enddate");
        }
        if($this->object->getShowDescription())
        {
            array_push($columns,$this->mytablegui->defineColumn($this->column_names[3],'description',$this->table_id.'_description',$description_width,'text',true));
            array_push($this->direction_array,"description");
        }
        if($this->object->getShowCreatedBy())
        {
            array_push($columns,$this->mytablegui->defineColumn($this->column_names[4],'created_by',$this->table_id.'_created_by',$width,'text',true));
            array_push($this->direction_array,"created_by");
        }
        if($this->object->getShowUpdatedBy())
        {
            array_push($columns,$this->mytablegui->defineColumn($this->column_names[5],'updated_by',$this->table_id.'_updated_by',$width,'text',true));
            array_push($this->direction_array,"updated_by");
        }
        if($this->object->getStatusPosition())
        {
            array_push($columns,$this->mytablegui->defineColumn($this->column_names[7],'edit_status',$this->table_id.'_edit_status',3,'boolean',true,$this->optionen));
            array_push($this->direction_array,"edit_status");
        }
        if($this->object->getShowEditStatusButton() AND $this->object->getStatusPosition())
        {
            array_push($columns,$this->mytablegui->defineColumn($this->column_names[8],'id','',$width,'text',false));
            array_push($this->direction_array,"button");
        }




        $this->columns=$columns;
    }

    function applyMilestoneFilter()
    {
        global $ilCtrl;
        $this->mytablegui->writeFilterToSession();        // writes filter to session
        $this->mytablegui->resetOffset();
        $ilCtrl->redirect($this, 'showContent');
    }

    function resetMilestoneFilter()
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
        if($this->object->getIsCollectlist())$sql_string=substr($sql_string,0,strpos($sql_string,"WHERE")+5).' ';
        if($this->object->getIsCollectlist())$sql_string=$sql_string."milestone_id = ".$this->milestoneid." AND ";

        if(!$filter)
        {
            if($this->object->getIsCollectlist())$sql_string=$sql_string.$this->addIdsForCollectListAtString($this->getIdsForCollectTheirTasks());
            $sql_string=$sql_string.$this->noFilterAddString();
            $sql_string=$sql_string.$this->notAtStartdate();
        }else
        {
            $i=0;
            if($this->object->getIsCollectlist())$sql_string=$this->CollectListFilter('attechedto',$sql_string);
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
        $edit_acces=$this->getWorkStatusMode();
        $tasks= new ilTaskGUI($sql_string,$this->object,$this->refid,$this->table_id,$this->objid,$this->direction_array,$edit_acces);
        $task=$tasks->getTasks();
        $this->mytablegui->setData($task);
    }
    
    function getHTML()
    {
        return $this->mytablegui->getTableHTMLCODE();
    }




    
}