
<?php

//Externe Dateien einbinden
include_once("class.ilMyTableGUI.php");
include_once ('class.ilAdvancedButtonGUI.php');
include_once("./Services/Form/classes/class.ilTextInputGUI.php");
include_once ("./Services/Form/classes/class.ilCheckboxInputGUI.php");
include_once ("./Services/Form/classes/class.ilCheckboxGroupInputGUI.php");
include_once ("./Services/Form/classes/class.ilSelectInputGUI.php");
include_once ("./Services/Form/classes/class.ilDateDurationInputGUI.php");

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



//----------------------------------------------------------------------------------------------------------------------
    //Konstruktor
    function __construct($column_name_array,$object,$objectid,$ref_id,$optionen,$listnname)
    {

        //Variabeln setzen
        global $ilAccess;
        $this->heigthAndWidth="100%";
        $this->limit=10;
        $this->objid=$objectid;
        $this->object_tasklist=$object;
        $this->taskListRefId=$ref_id;
        $this->table_gui = new ilMyTableGUI($this, "showContent",'/Customizing/global/plugins/Services/Repository/RepositoryObject/Todolists','table3','Todolists','Tasklist');
        $this->table_gui->setTableTitle($listnname);
        $this->setMoreCounter(0);

        //Wenn User das Recht Edit_contet besitzt wird ein bearbeitungs und löschen Button in die Tabelle hinzugefügt 
        if ($ilAccess->checkAccess("edit_content", "",$ref_id ))
        {
            $this->table_gui->addActionButton();
        }

        $this->table_gui->setIsCalledByClass('ilObjTodolistsGUI');
        $this->table_gui->setFilterCommand("applyFilter");        // parent GUI class must implement this function
        $this->table_gui->setResetCommand("resetFilter");

        //Berechnet die Breite der einzelnen Zeilen
        $columns = array();
        $width=$this->getWidth();
        $description_width=$this->getDescriptionWidth();
        $width=$this->correctWidth($width,$description_width);

        //Hier werden die Spalten sowie deren Position gesetzt
        //Abfrage ob Spalte aktiv
        if(!$this->object_tasklist->getStatusPosition())   array_push($columns,$this->table_gui->defineColumn($column_name_array[7],'edit_status','edit_status',3,'boolean',true,$optionen));
        if($this->object_tasklist->getShowEditStatusButton() AND !$this->object_tasklist->getStatusPosition()) array_push($columns,$this->table_gui->defineColumn($column_name_array[8],'id','',$width,'text',false));
        array_push($columns,$this->table_gui->defineColumn($column_name_array[0],'tasks','tasks',$width,'text',true) );
        if($this->object_tasklist->getShowStartDate())	array_push($columns,$this->table_gui->defineColumn($column_name_array[1],'startdate','startdate',$width,'date',true));
        if($this->object_tasklist->getShowEnddate())array_push($columns,$this->table_gui->defineColumn($column_name_array[2],'enddate','enddate',$width,'date',true));
        if($this->object_tasklist->getShowDescription())array_push($columns,$this->table_gui->defineColumn($column_name_array[3],'description','description',$description_width,'text',true));
        if($this->object_tasklist->getShowCreatedBy())	array_push($columns,$this->table_gui->defineColumn($column_name_array[4],'created_by','created_by',$width,'text',true));
        if($this->object_tasklist->getShowUpdatedBy())	array_push($columns,$this->table_gui->defineColumn($column_name_array[5],'updated_by','updated_by',$width,'text',true));
        if($this->object_tasklist->getIsCollectlist())		array_push($columns,$this->table_gui->defineColumn($column_name_array[6],'','attechedto',$width,'text',true));
        if($this->object_tasklist->getStatusPosition())   array_push($columns,$this->table_gui->defineColumn($column_name_array[7],'edit_status','edit_status',3,'boolean',true,$optionen));
        if($this->object_tasklist->getShowEditStatusButton() AND $this->object_tasklist->getStatusPosition()) array_push($columns,$this->table_gui->defineColumn($column_name_array[8],'id','',$width,'text',false));

        $this->columns=$columns;
        $this->table_gui->setColumns($columns);
        
        
        if($this->object_tasklist->getBeforeStartdateShown())
        {
            $ti = new ilCheckboxInputGUI($column_name_array[9], "extra_0");
            $this->table_gui->setExtraFilter($ti);
        }
        $this->table_gui->initFilter();

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
            return ' AND startdate <='.$ilDB->quote(time(),"integer");
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
    //------------------------------------------------------------------------------------------------------------------
    //Filter Einstellung für createdby und updatedby
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
//Funktionen für die Ausgabe der Status Spalte

    //Bestimmung welche Bilder ausgegeben müssen (abhängig davon ob der Statusändern Button aktiv ist)
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
        if ( ($this->object_tasklist->getEditStatusPermission() AND !$ilAccess->checkAccess("edit_content", "", $this->taskListRefId)) OR $this->object_tasklist->getShowEditStatusButton() )
        {
            return true;
        }
        return false;
    }
    //------------------------------------------------------------------------------------------------------------------
    //Rückgabe der Grafiken in Form eines image
    //Endung Image ist der Fall falls kein Button aktiv ist
    protected function getOkIconImage()
    {
        return "<img heigth='".$this->heigthAndWidth."' width='".$this->heigthAndWidth."' src=\"Customizing/global/plugins/Services/Repository/RepositoryObject/Todolists/templates/images/icon_ok.svg\">";
    }

    protected function getNotOkIconImage()
    {
        $src_not_ok="Customizing/global/plugins/Services/Repository/RepositoryObject/Todolists/templates/images/icon_not_ok.svg";
        $src_ok="Customizing/global/plugins/Services/Repository/RepositoryObject/Todolists/templates/images/icon_ok.svg";
        $src_mouseover="Customizing/global/plugins/Services/Repository/RepositoryObject/Todolists/templates/images/icon_mouseover.svg";
        if(!$this->object_tasklist->getShowEditStatusButton())return "<img heigth='".$this->heigthAndWidth."' width='".$this->heigthAndWidth."' src='".$src_not_ok."' onmouseover=\"src='". $src_mouseover."'\" onmouseout=\"src='".$src_not_ok."'\" />";
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
    //------------------------------------------------------------------------------------------------------------------
    //holt sich den benötigten Link für die Statusbilder um eine weiterleitung zu veranlassen
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
    //------------------------------------------------------------------------------------------------------------------
    //Gibt den Status ändern Button zurück
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
//----------------------------------------------------------------------------------------------------------------------
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
                    if ($this->object_tasklist->getEnddateWarning() AND $enddate_time_stamp < time()) {
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
// Daten sortieren und verändern

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
        if($this->object_tasklist->getEnddateCursive())
        {
            $date='<i>'.$date.'</i>';
        }
        if($this->object_tasklist->getEnddateFat())
        {
            $date='<b>'.$date.'</b>';
        }


        $css="<style>
        .enddate
        {
            color: #".$this->object_tasklist->getEnddateColor()." ;
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
                    if(!$this->object_tasklist->getStatusPosition() AND $this->object_tasklist->getShowEditStatusButton())$sorted_record["fertig"]=$record["fertig"];
                    $sorted_record[$key] = $value;
                    break;
                case "edit_status":
                    if($this->object_tasklist->getStatusPosition() AND $this->object_tasklist->getIsCollectlist())$sorted_record["attechedto"]=$record["attechedto"];
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
                    if ($this->object_tasklist->getShowEditStatusButton()) $sorted_record['fertig'] = $this->setChangeButton($value, $edit_status);
                    if ($this->object_tasklist->getIsCollectlist())$sorted_record["attechedto"]=$this->getWorklistLinkForCollectlist($value);
                    break;
                case "description":
                    if(strlen($record[$key]) > 100)
                    {



                        $pos = strripos($record[$key]," ");
                        if($pos==false)$pos=100;
                        $string=substr($record[$key],0,100);
                        $pos = strripos($string," ");
                        if($pos==false)$pos=100;
                        $string=substr($record[$key],0,$pos+1);

                        $in=str_replace(" ","_",$table_id.$this->getMoreCounter());
                        $in=str_replace("[","",$in);
                        $in=str_replace("]","",$in);


                        $link_mehr='<a onClick="changeMehr(\'all_'.$in.'\',\'weniger_'.$in.'\')">...weiter lesen.</a>';
                        $link_weniger='<a onClick="changeWeniger(\'all_'.$in.'\',\'weniger_'.$in.'\')"> ...zuklappen.</a>';
                        $string=$string.$link_mehr;
                        $div="<div id = 'more_".$in."'>
                                <div id = 'weniger_".$in."'>".$string."</div>
                                <div id = 'all_".$in."'>".$record[$key].$link_weniger."</div>
                              </div>";
                        $css="<style>
                            #all_".$in."
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




    function formatDate($date)
    {
        return $this->timestampInDate(strtotime($date));
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



