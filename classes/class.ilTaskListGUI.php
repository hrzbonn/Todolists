<?php

//Externe Dateien einbinden
include_once("class.ilMyTableGUI.php");
include_once ('class.ilAdvancedButtonGUI.php');
include_once("./Services/Form/classes/class.ilTextInputGUI.php");
include_once ("./Services/Form/classes/class.ilCheckboxInputGUI.php");
include_once ("./Services/Form/classes/class.ilCheckboxGroupInputGUI.php");
include_once ("./Services/Form/classes/class.ilSelectInputGUI.php");
include_once ("./Services/Form/classes/class.ilDateDurationInputGUI.php");
include_once ("class.ilTaskGUI.php");

//----------------------------------------------------------------------------------------------------------------------
class ilTaskListGUI
{
    //Variabeln deklarieren
    private $table_gui;
    private $columns;
    private $objid;
    private $limit;
    private $taskListRefId;
    private $heigthAndWidth;
    private $object_tasklist;
    private $isMilestonelist;
    private $table_id;
    private $direction_array;
    private $column_name_array;
    private $optionen;


//----------------------------------------------------------------------------------------------------------------------
    //Konstruktor
    function __construct($column_name_array,$object,$objectid,$ref_id,$optionen,$listnname)
    {

        //Variabeln setzen
        global $ilAccess;
        $this->optionen=$optionen;
        $this->heigthAndWidth="100%";
        $this->limit=10;
        $this->objid=$objectid;
        $this->object_tasklist=$object;
        $this->taskListRefId=$ref_id;
        $this->table_gui = new ilMyTableGUI($this, "showContent",'/Customizing/global/plugins/Services/Repository/RepositoryObject/Todolists','table3','Todolists','Tasklist');
        $this->table_gui->setTableTitle($listnname);
        $this->isMilestonelist=false;
        $this->table_id="Tasklist";
        $this->direction_array=array();
        $this->column_name_array=$column_name_array;

        //Wenn User das Recht Edit_contet besitzt wird ein bearbeitungs und löschen Button in die Tabelle hinzugefügt
        if ($ilAccess->checkAccess("edit_content", "",$ref_id ))
        {
            $this->table_gui->addOwnActionButton();
        }

        $this->table_gui->setIsCalledByClass('ilObjTodolistsGUI');
        $this->table_gui->setFilterCommand("applyFilter");        // parent GUI class must implement this function
        $this->table_gui->setResetCommand("resetFilter");

        $this->setColumns();
        $this->table_gui->setColumns($this->columns);


        if($this->object_tasklist->getBeforeStartdateShown())
        {
            $ti = new ilCheckboxInputGUI($column_name_array[9], "extra_0");
            $this->table_gui->setExtraFilter($ti);
        }
        $this->table_gui->initFilter();

    }

    function setColumns()
    {
        //Berechnet die Breite der einzelnen Zeilen
        $columns = array();
        $width=$this->getWidth();
        $description_width=$this->getDescriptionWidth();
        $width=$this->correctWidth($width,$description_width);

        //Hier werden die Spalten sowie deren Position gesetzt
        //Abfrage ob Spalte aktiv
        if(!$this->object_tasklist->getStatusPosition())
        {
            array_push($columns,$this->table_gui->defineColumn($this->column_name_array[7],'edit_status','edit_status'.$this->objid,3,'boolean',true,$this->optionen));
            array_push($this->direction_array,"edit_status");
        }
        if($this->object_tasklist->getShowEditStatusButton() AND !$this->object_tasklist->getStatusPosition())
        {
            array_push($columns,$this->table_gui->defineColumn($this->column_name_array[8],'id','',$width,'text',false));
            array_push($this->direction_array,"button");
        }
        array_push($columns,$this->table_gui->defineColumn($this->column_name_array[0],'tasks','tasks'.$this->objid,$width,'text',true) );
        array_push($this->direction_array,"tasks");
        if($this->object_tasklist->getShowStartDate())
        {
            array_push($columns,$this->table_gui->defineColumn($this->column_name_array[1],'startdate','startdate'.$this->objid,$width,'date',true));
            array_push($this->direction_array,"startdate");
        }
        if($this->object_tasklist->getShowEnddate())
        {
            array_push($columns,$this->table_gui->defineColumn($this->column_name_array[2],'enddate','enddate'.$this->objid,$width,'date',true));
            array_push($this->direction_array,"enddate");
        }
        if($this->object_tasklist->getShowDescription())
        {
            array_push($columns,$this->table_gui->defineColumn($this->column_name_array[3],'description','description'.$this->objid,$description_width,'text',true));
            array_push($this->direction_array,"description");
        }
        if($this->object_tasklist->getShowCreatedBy())
        {
            array_push($columns, $this->table_gui->defineColumn($this->column_name_array[4], 'created_by', 'created_by'.$this->objid, $width, 'text', true));
            array_push($this->direction_array,"created_by");
        }
        if($this->object_tasklist->getShowUpdatedBy())
        {
            array_push($columns,$this->table_gui->defineColumn($this->column_name_array[5],'updated_by','updated_by'.$this->objid,$width,'text',true));
            array_push($this->direction_array,"updated_by");
        }
        if($this->object_tasklist->getIsCollectlist())
        {
            array_push($columns,$this->table_gui->defineColumn($this->column_name_array[6],'','attechedto'.$this->objid,$width,'text',true));
            array_push($this->direction_array,"attechedto");
        }
        if($this->object_tasklist->getStatusPosition())
        {
            array_push($columns,$this->table_gui->defineColumn($this->column_name_array[7],'edit_status','edit_status'.$this->objid,3,'boolean',true,$this->optionen));
            array_push($this->direction_array,"edit_status");
        }
        if($this->object_tasklist->getShowEditStatusButton() AND $this->object_tasklist->getStatusPosition())
        {
            array_push($columns,$this->table_gui->defineColumn($this->column_name_array[8],'id','',$width,'text',false));
            array_push($this->direction_array,"button");
        }

        $this->columns=$columns;
    }

//----------------------------------------------------------------------------------------------------------------------
    //Filter aufruf
    function applyFilter()
    {
        global $ilCtrl;
        $this->table_gui->writeFilterToSession();        // writes filter to session
        $this->table_gui->resetOffset();
        //weiterleitung auf den Inhalt der Seite
        $ilCtrl->redirect($this, 'showContent');
    }
    //Filter reset
    function resetFilter()
    {
        global $ilCtrl;
        $this->table_gui->resetOffset();                // sets record offest to 0 (first page)
        $this->table_gui->resetFilter();
        $ilCtrl->redirect($this, 'showContent');
    }
    //Zurücksetzten der Tabelle auf Seite 1 nach Filter aufruf oder reset
    public function resetOffset()
    {
        $this->table_gui->resetOffset();
    }
//----------------------------------------------------------------------------------------------------------------------
    //Variable Datenbankabfrage für die Datenbank rep_robj_xtdo_data
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
    //gibt zurück ob es sich um einen Meilenstein in einer Sammelliste handelt
    protected function getIsMilestonInCollectlist()
    {
        if(!$this->isMilestonelist)
            return true;
        else return false;
    }
//----------------------------------------------------------------------------------------------------------------------
//Berechnet die richtige größe der verschiedenen Spalten und gibt diese zurück
//aufruf Konstruktor
    protected function correctWidth($width,$description_width)
    {
        if($description_width==0)return $width;

        $columnnumber=1;
        if($this->object_tasklist->getShowStartDate())$columnnumber++;
        if($this->object_tasklist->getShowCreatedBy())$columnnumber++;
        if($this->object_tasklist->getShowUpdatedBy())$columnnumber++;
        if($this->object_tasklist->getIsCollectlist())$columnnumber++;
        if($this->object_tasklist->getShowEditStatusButton())$columnnumber++;
        if($this->object_tasklist->getShowEnddate())$columnnumber++;

        return (97-$description_width)/$columnnumber;

    }

    protected function getDescriptionWidth()
    {
        if(!$this->object_tasklist->getShowDescription())return 0;
        return 97/2;
    }

    protected function getWidth()
    {
        $columnnumber=1;
        if($this->object_tasklist->getShowStartDate())$columnnumber++;
        if($this->object_tasklist->getShowCreatedBy())$columnnumber++;
        if($this->object_tasklist->getShowUpdatedBy())$columnnumber++;
        if($this->object_tasklist->getIsCollectlist())$columnnumber++;
        if($this->object_tasklist->getShowEditStatusButton())$columnnumber++;
        if($this->object_tasklist->getShowEnddate())$columnnumber++;
        if($this->object_tasklist->getShowDescription())$columnnumber++;

        return 97/$columnnumber;
    }
//----------------------------------------------------------------------------------------------------------------------
//Funktionen die von anderen Klassen aufgerufen werden müssen um diese Klasse nutzen zu können

    //Setzt das ilObjTodolists Objekt
    protected function setObject($object)
    {
        $this->object_tasklist=$object;
    }
    //------------------------------------------------------------------------------------------------------------------
    //Setzt die Größe der Statusbilder
    public function setHeigthAndWidth($heigthAndWidth)
    {
        $this->heigthAndWidth=$heigthAndWidth;
    }
    //------------------------------------------------------------------------------------------------------------------
    //Setzt die Spalten der Aufgabenliste
    function setTasklistColumns($value)
    {
        $this->columns=$value;
    }
    //------------------------------------------------------------------------------------------------------------------
    //Setzt die Aufgabenlisten Id
    function setTasklistObjectId($value)
    {
        $this->objid=$value;
    }
    //Wird aufgerufen um zu zeigen dass es sich um eine MilestoneTasklist handelt
    function setIsMilestoneListTrue()
    {
        $this->isMilestonelist=true;
    }
    //
    function setTableId($value)
    {
        $this->table_id=$value;
    }
//----------------------------------------------------------------------------------------------------------------------
//Filter Funktionen
//Diese fügen dem Sql String etwas hinzu sodass der Datenbankaufruf den jeweiligen Filter berücksichtigen muss

    //Filter Option für die Einstellung ob Aufgaben angezeigt werden sollen wenn das Startdatum noch nicht erreicht ist
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
            $date = date("Y-m-d",time());
            return ' AND startdate <='.$ilDB->quote($date,"text");
        }else
        {
            return "";
        }
    }
    //------------------------------------------------------------------------------------------------------------------
    //Filter um nach Zugehörigen Listen filtern zu können
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
    //------------------------------------------------------------------------------------------------------------------
    //Einstellungen für den Fall das kein Filter aktiv ist
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
    //------------------------------------------------------------------------------------------------------------------
    //Filter einstellungen für eine Checkboxfiltereinstellung
    function BoolFilter($parameter)
    {
        global $ilDB;
        if(isset($parameter))
        {
            $wert= $this->table_gui->filter[$parameter];
            $parameter=str_replace($this->objid,"",$parameter);
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
    //------------------------------------------------------------------------------------------------------------------
    //Filter einstellung für ein Textfeld
    private function TextFilter($parameter)
    {
        global $ilDB;
        if($this->table_gui->filter[$parameter] != '')
        {
            $string=mysql_escape_string ( $this->table_gui->filter[$parameter] );
            $parameter=str_replace($this->objid,"",$parameter);
            return " AND ". $parameter ." LIKE ". $ilDB->quote('%'.$string.'%',"text");
        }else
        {
            return '';
        }
    }
    //------------------------------------------------------------------------------------------------------------------
    //Filter Einstellung für createdby und updatedby
    function specialFilter($parameter)
    {
        global $ilDB;
        if($this->table_gui->filter[$parameter] != '')
        {
            global $ilUser;
            $integer=mysql_escape_string ( $this->table_gui->filter[$parameter] );
            $parameter=str_replace($this->objid,"",$parameter);
            return " AND ". $parameter ." = ". $ilDB->quote($ilUser->getUserIdByLogin($integer),"integer");
        }else
        {
            return '';
        }
    }
    //------------------------------------------------------------------------------------------------------------------
    //Filter Einstellung für Datum
    function DateFilter($parameter)
    {
        global $ilDB;


        if(isset($this->table_gui->filter[$parameter."_from"]) OR isset($this->mytablegui->filter[$parameter."_to"]))
        {
            $startdate=$this->table_gui->filter[$parameter."_from"];
            $enddate=$this->table_gui->filter[$parameter."_to"];


            $startdate = $startdate->getUnixTime();
            $enddate = $enddate->getUnixTime();

            $parameter=str_replace($this->objid,"",$parameter);
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
//----------------------------------------------------------------------------------------------------------------------

    protected function getWorkStatusMode()
    {
        global $ilAccess;
        if ( ($this->object_tasklist->getEditStatusPermission() AND !$ilAccess->checkAccess("edit_content", "", $this->taskListRefId)) OR $this->object_tasklist->getShowEditStatusButton() )
        {
            return true;
        }
        return false;
    }

//Datenbank Funktionen


    protected function setDataFromDb($setData)
    {
        $this->table_gui->setmyData($setData);
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
    function getDataFromDb($filter = false)
    {

        $sql_string=$this->getSqlString();
        if($this->object_tasklist->getIsCollectlist())$sql_string=substr($sql_string,0,strpos($sql_string,"WHERE")+5).' ';
        if($this->object_tasklist->getIsCollectlist())$sql_string=$sql_string."milestone_id = 0 AND ";

        if(!$filter)
        {
            if($this->object_tasklist->getIsCollectlist())$sql_string=$sql_string.$this->addIdsForCollectListAtString($this->getIdsForCollectTheirTasks());
            $sql_string=$sql_string.$this->noFilterAddString();
            $sql_string=$sql_string.$this->notAtStartdate();
        }else
        {
            $i=0;
            if($this->object_tasklist->getIsCollectlist())$sql_string=$this->CollectListFilter('attechedto',$sql_string);
            $sql_string=$sql_string.$this->notAtStartdateFilter();
            foreach($this->columns as $column)
            {

                if ($column['type'] == "text" AND $column['sort_and_filter'] != 'attechedto'.$this->objid AND $column['sort_and_filter'] != 'created_by'.$this->objid AND $column['sort_and_filter'] != 'updated_by'.$this->objid ) {
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



    function setDBdata($sql_string)
    {
        $edit_acces=$this->getWorkStatusMode();
        $tasks= new ilTaskGUI($sql_string,$this->object_tasklist,$this->taskListRefId,$this->table_id,$this->objid,$this->direction_array,$edit_acces);
        $task=$tasks->getTasks();
        $this->setDataFromDb($task);
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
//----------------------------------------------------------------------------------------------------------------------
//Sonstige

    //Gibt Tabelle als HtmlCode zurück fertig zur ausgabe
    function getTable()
    {
        return $this->table_gui->getTableHTMLCODE();
    }
}
?>
