<?php


include_once ('Services/Table/classes/class.ilTable2GUI.php');
include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
include_once ('Services/UIComponent/AdvancedSelectionList/classes/class.ilAdvancedSelectionListGUI.php');
include_once("./Services/Form/classes/class.ilTextInputGUI.php");
include_once ("./Services/Form/classes/class.ilCheckboxInputGUI.php");
include_once ("./Services/Form/classes/class.ilCheckboxGroupInputGUI.php");
include_once ("./Services/Form/classes/class.ilSelectInputGUI.php");
include_once ("./Services/Form/classes/class.ilDateDurationInputGUI.php");

class ilMyTableGUI extends ilTable2GUI
{
    private $tableTitle;
    private $actionbutton_exists;
    private $is_called_by_class;
    private $rowtemplate;
    private $rowtemplatepath;
    private $mycolumns;
    private $is_id_shown;
    private $isFilterShown;
    private $pluginname;
    private $extra_filter;
    private $table_id;


    //------------------------------------------------------------------------------------------------------------------

    //Konstruktor initialisiert Variabeln mit Default-Werten und setzt das Template
    function __construct($a_parent_obj, $a_parent_cmd,$template_path,$templateName,$pluginname,$tableid)
    {
        global $ilCtrl,$ilUser;
        $this->setPluginname($pluginname);
        $identifier=$tableid.'_'.$ilUser->getLoginByUserId($ilUser->getid());
        $this->setId($identifier);
        $this->setPrefix($identifier);
        $this->setFormName($identifier);
        $this->table_id=$tableid;

        parent::__construct($a_parent_obj, $a_parent_cmd);

        $this->setIsFilterShown(false);
        $this->setShowRowsSelector(true);
        //$this->setShowTemplates(true);

        
        $this->extra_filter=array();
        $this->is_id_shown=false;
        $this->actionbutton_exists=false;
        $this->setFormAction($ilCtrl->getFormAction($a_parent_obj));
        // sets the template which describe the appearance of the table
        $this->rowtemplate="tpl.".$templateName.".html";
        $this->rowtemplatepath=$template_path;
        $this->setRowTemplate($this->rowtemplate,
            $template_path);
        
    }

    
    
    
    
    
    
    //------------------------------------------------------------------------------------------------------------------

    //Setzt und initialisiert die Filter der Tabelle

    function initFilter()
    {

        foreach ($this->getColumns() as $column)
        {
            $ti=$this->getFilter($column);
            if($column["set_filter"])
            {


                if($column['type'] == "date") {
                    $ti = $this->addFilterItemByMetaType($column["sort_and_filter"],6,false,$column["column_name"]);

                    $date=$ti->getValue();
                    $date_to=$date["to"];
                    $date_from=$date["from"];

                    $this->filter[$column["sort_and_filter"]."_from"] =$date_from;
                    $this->filter[$column["sort_and_filter"]."_to"]  =$date_to;


                }else
                {
                    $this->addFilterItem($ti);
                    $ti->readFromSession();
                    $daten = $ti->getValue();
                    $this->filter[$column["sort_and_filter"]] = $ti->getValue();
                }
            }

        }

        
        $count=0;
        foreach ($this->extra_filter as $ti)
        {
            $this->addFilterItem($ti);
            $ti->readFromSession();        // get currenty value from session (always after addFilterItem())
            $this->filter["extra_".$count] = $_POST["extra_".$count];
            $count++;
        }
        

    }
    private function getFilter($column)
    {

        if ($column["type"] == "text") {
            $filter = new ilTextInputGUI($column['column_name'], $column['sort_and_filter']);
            $filter->setMaxLength(64);
            $filter->setSize(20);
            return $filter;
        }
        if ($column["type"] == "boolean") {
            $filter = new ilSelectInputGUI($column['column_name'], $column['sort_and_filter']);
            $filter->setOptions($column['boolean_options']);
            return $filter;
        }
    }
    
    function setExtraFilter($filter)
    {
        array_push($this->extra_filter,$filter);
    }



    //------------------------------------------------------------------------------------------------------------------

    //Hilfsfunktion zum erstellen einer Spalte
    function defineColumn($column_name,$database_name,$sort_field,$width,$type,$set_filter=false,$optionen=array())
    {
        $column['column_name']=$column_name;
        $column['database_name']=$database_name;
        $column['sort_and_filter']=$sort_field;
        $column['width']=$width;
        $column['type']=$type;
        $column['set_filter']=$set_filter;
        $column['boolean_options']=$optionen;

        return $column ;
    }

    //------------------------------------------------------------------------------------------------------------------

    //Setzt alle Spalten diese müssen sich in einem array befinden und über defineColumn() definiert werden
    function setColumns($coulmn_array=array())
    {
        $this->mycolumns=$coulmn_array;
    }
    //Gibt alle Spalten zurück
    function getColumns()
    {
        return $this->mycolumns;
    }

    //------------------------------------------------------------------------------------------------------------------

    //Setzt den Tabellentitel
    function setTableTitle($title)
    {
        $this->tableTitle=$title;
    }

    //------------------------------------------------------------------------------------------------------------------

    //setzt ob der Filter Standardmäßig geöffnet oder geschlossen ist
    public function setIsFilterShown($isFilterShown = false)
    {
        $this->isFilterShown = $isFilterShown;
    }
    //gibt zurück ob der Filter Standardmäßig geöffnet oder geschlossen ist
    private function getIsFilterShown()
    {
        return $this->isFilterShown;
    }

    //------------------------------------------------------------------------------------------------------------------

    //Setzt den Pluginnamen
    private function setPluginname($pluginname)
    {
        $this->pluginname=$pluginname;
    }
    //Gibt den Pluginnamen zurück
    private function getPluginname()
    {
        return $this->pluginname;
    }

    //------------------------------------------------------------------------------------------------------------------

    //Setzt die Anzahl der Zeilen die Maximal angezeigt werden sollen
    function setMyLimit($limit)
    {
        $this->setLimit($limit);
    }
    
    //------------------------------------------------------------------------------------------------------------------

    //Setzt das Feld nach dem beim Anzeigen der Tabelle standardmäßig sortiert werden soll
    //Standard ist keine Sortierung
    function setstartOrderfield($order_field)
    {
        $this->setDefaultOrderField($order_field);
    }

    //------------------------------------------------------------------------------------------------------------------

    //Setzt die Daten welche in die Zeilen kommen sollen
    //Muss ein Array der folgenden Form sein: array(array('row1 column1','row1 column2'),array('row2 column1','row 2 column2'))
    //Die Arrays im Array müssen assoziativ sein
    function setmyData($data)
    {
        $this->setData($data);
    }

    //------------------------------------------------------------------------------------------------------------------

    //Fügt einen Actionbutton zur Tabelle hinzu für das Bearbeiten und Löschen einer Zeile
    function addActionButton()
    {
        $this->actionbutton_exists=true;
    }
    //Definiert den Actionbutton
    //WICHTIG: edit_row,delete_row sind die cmd Befehle für das Löschen und Bearbeiten
    //WICHTIG: die id sollte im Datenarray als letztes sein damit der Button auch an letzter Stelle steht
    private function actionButton($id)
    {
        global $lng,$ilCtrl;
        $ilCtrl->setParameterByClass("$this->is_called_by_class", "data_id",$id);
        $alist = new ilAdvancedSelectionListGUI();
        $alist->setId($id);
        $alist->setListTitle($lng->txt("actions"));
        $alist->addItem($lng->txt('edit'), 'edit_row',$ilCtrl->getLinkTargetByClass($this->is_called_by_class, 'edit_row'));
        $alist->addItem($lng->txt('delete'), 'delete_row',$ilCtrl->getLinkTargetByClass($this->is_called_by_class, 'delete_row'));
        return $alist->getHTML();
    }
    //Setzt welche Klasse die Tabelle ruft, d.h. in welcher Klasse die Cmd Befehle des Actionbuttons definiert sind
    function setIsCalledByClass($callingclass)
    {
        $this->is_called_by_class=$callingclass;
    }

    //------------------------------------------------------------------------------------------------------------------
    
    //Setzt ob die Id Angezeigt werden soll, da der Actionbutton diese Aufjedenfall benötigt kann eingestellt werden
    //Ob sie Angezeigt werden soll oder nicht 
    function setIsIdShown($value)
    {
        $this->is_id_shown=$value;
    }
    
    //------------------------------------------------------------------------------------------------------------------
    

    //Beendet die Tabelle und gibt deren HTML-Code zurück
    function getTableHTMLCODE()
    {
        global $lng;

        foreach ($this->getColumns() as $column) {

            if($column["database_name"]=="") {
                $sort = $column["sort_and_filter"];
            }else
            {
                $sort = $column["database_name"];
            }



            $this->addColumn($column["column_name"], $sort, $column["width"] . "%");
        }
        
        if($this->actionbutton_exists)
        {
            $this->addColumn($lng->txt('actions'));
        }
        
        $this->setTitle($this->tableTitle);
        
        return $this->getHTML();
    }

    //------------------------------------------------------------------------------------------------------------------
    
    //Gibt die Daten an das Standardtemplate zur Ausgabe weiter
    protected function fillRow($a_set)
    {
        $this->tpl->setCurrentBlock("tbl_content_cell");
        foreach ((array) $a_set as $key => $value)
        {
            if(!$this->is_id_shown)
            {
                if($key != 'id') {
                    if ($value == false OR $value == NULL OR strlen($value) == 0) {
                        $value = ' ';
                    }
                    $this->tpl->setVariable("TBL_CONTENT_CELL", $value);
                    $this->tpl->parseCurrentBlock();
                }
            }else
            {
                if ($value == false OR $value == NULL OR strlen($value) == 0) {
                    $value = ' ';
                }
                $this->tpl->setVariable("TBL_CONTENT_CELL", $value);
                $this->tpl->parseCurrentBlock();
            }
        }
        if($this->actionbutton_exists)
        {
            $this->tpl->setVariable('TBL_CONTENT_CELL',$this->actionbutton($a_set['id']));
            $this->tpl->parseCurrentBlock();
        }
        $this->tpl->setCurrentBlock("tbl_content_row");
        $this->tpl->parseCurrentBlock();
    }


    //------------------------------------------------------------------------------------------------------------------
    
    //Überschreibt die Funktion render() Der ilTable2GUI Klasse
    //Ist die gleiche Funktion wie in der ilTable2GUI nur das renderFilter() durch ownRenderFilter() ersetzt
    //wurde um es zu ermöglichen, dass der Filter standardmäßig zugeklappt ist
    function render()
    {
        global $lng, $ilCtrl;

        $this->tpl->setVariable("CSS_TABLE",$this->getStyle("table"));
        $this->tpl->setVariable("DATA_TABLE", (int) $this->getIsDataTable());
        if ($this->getId() != "")
        {
            $this->tpl->setVariable("ID", 'id="'.$this->getId().'"');
        }

        // description
        if ($this->getDescription() != "")
        {
            $this->tpl->setCurrentBlock("tbl_header_description");
            $this->tpl->setVariable("TBL_DESCRIPTION", $this->getDescription());
            $this->tpl->parseCurrentBlock();
        }

        if(!$this->getPrintMode())
        {
            $this->ownRenderFilter();
        }

        if ($this->getDisplayAsBlock())
        {
            $this->tpl->touchBlock("outer_start_1");
            $this->tpl->touchBlock("outer_end_1");
        }
        else
        {
            $this->tpl->touchBlock("outer_start_2");
            $this->tpl->touchBlock("outer_end_2");
        }

        // table title and icon
        if ($this->enabled["title"] && ($this->title != ""
                || $this->icon != "" || count($this->header_commands) > 0 ||
                  $this->close_command != ""))
        {
            if ($this->enabled["icon"])
            {
                $this->tpl->setCurrentBlock("tbl_header_title_icon");
                $this->tpl->setVariable("TBL_TITLE_IMG",ilUtil::getImagePath($this->icon));
                $this->tpl->setVariable("TBL_TITLE_IMG_ALT",$this->icon_alt);
                $this->tpl->parseCurrentBlock();
            }

            if(!$this->getPrintMode())
            {
                foreach($this->header_commands as $command)
                {
                    if ($command["img"] != "")
                    {
                        $this->tpl->setCurrentBlock("tbl_header_img_link");
                        if ($command["target"] != "")
                        {
                            $this->tpl->setVariable("TARGET_IMG_LINK",
                                'target="'.$command["target"].'"');
                        }
                        $this->tpl->setVariable("ALT_IMG_LINK", $command["text"]);
                        $this->tpl->setVariable("HREF_IMG_LINK", $command["href"]);
                        $this->tpl->setVariable("SRC_IMG_LINK",
                            $command["img"]);
                        $this->tpl->parseCurrentBlock();
                    }
                    else
                    {
                        $this->tpl->setCurrentBlock("head_cmd");
                        $this->tpl->setVariable("TXT_HEAD_CMD", $command["text"]);
                        $this->tpl->setVariable("HREF_HEAD_CMD", $command["href"]);
                        $this->tpl->parseCurrentBlock();
                    }
                }
            }

            // close command
            if ($this->close_command != "")
            {
                $this->tpl->setCurrentBlock("tbl_header_img_link");
                $this->tpl->setVariable("ALT_IMG_LINK",$lng->txt("close"));
                $this->tpl->setVariable("HREF_IMG_LINK",$this->close_command);
                $this->tpl->parseCurrentBlock();
            }

            $this->tpl->setCurrentBlock("tbl_header_title");
            $this->tpl->setVariable("TBL_TITLE",$this->title);
            $this->tpl->setVariable("TOP_ANCHOR",$this->getTopAnchor());
            if ($this->getDisplayAsBlock())
            {
                $this->tpl->setVariable("BLK_CLASS", "Block");
            }
            $this->tpl->parseCurrentBlock();
        }

        // table header
        if ($this->enabled["header"])
        {
            $this->fillHeader();
        }

        $this->tpl->touchBlock("tbl_table_end");

        return $this->tpl->get();
    }

    //------------------------------------------------------------------------------------------------------------------
    
    //Ist die gleiche Funktion wie renderFilter() aus der Klasse ilTable2GUI
    //Einzige Änderung ist die if-Abfrage ob die Standard js-Datei oder die bearbeitete js-Datei genutzt werden soll
    //Die Abgeänderte js-Datei hat den Filter Standardmäßig zugeklappt wohin beim Orginal der Filter standardmäßig zugeklappt ist
    private function ownRenderFilter()
    {
        global $lng, $tpl;

        $filter = $this->getFilterItems();
        $opt_filter = $this->getFilterItems(true);


        if(!$this->getIsFilterShown())
        {
            $tpl->addJavascript("./Customizing/global/plugins/Services/Repository/RepositoryObject/".$this->getPluginname()."/js/ServiceTable.js");
        }else
        {
            $tpl->addJavascript("./Services/Table/js/ServiceTable.js");
        }

        if (count($filter) == 0 && count($opt_filter) == 0)
        {
            return;
        }

        include_once("./Services/YUI/classes/class.ilYuiUtil.php");
        ilYuiUtil::initConnection();

        $ccnt = 0;

        // render standard filter
        if (count($filter) > 0)
        {
            foreach ($filter as $item)
            {
                if ($ccnt >= $this->getFilterCols())
                {
                    $this->tpl->setCurrentBlock("filter_row");
                    $this->tpl->parseCurrentBlock();
                    $ccnt = 0;
                }
                $this->tpl->setCurrentBlock("filter_item");
                $this->tpl->setVariable("OPTION_NAME",
                    $item->getTitle());
                $this->tpl->setVariable("F_INPUT_ID",
                    $item->getFieldId());
                $this->tpl->setVariable("INPUT_HTML",
                    $item->getTableFilterHTML());
                $this->tpl->parseCurrentBlock();
                $ccnt++;
            }
        }

        // render optional filter
        if (count($opt_filter) > 0)
        {
            $this->determineSelectedFilters();

            foreach ($opt_filter as $item)
            {
                if($this->isFilterSelected($item->getPostVar()))
                {
                    if ($ccnt >= $this->getFilterCols())
                    {
                        $this->tpl->setCurrentBlock("filter_row");
                        $this->tpl->parseCurrentBlock();
                        $ccnt = 0;
                    }
                    $this->tpl->setCurrentBlock("filter_item");
                    $this->tpl->setVariable("OPTION_NAME",
                        $item->getTitle());
                    $this->tpl->setVariable("F_INPUT_ID",
                        $item->getFieldId());
                    $this->tpl->setVariable("INPUT_HTML",
                        $item->getTableFilterHTML());
                    $this->tpl->parseCurrentBlock();
                    $ccnt++;
                }
            }

             // filter selection
              $items = array();
              foreach ($opt_filter as $item)
              {
                  $k = $item->getPostVar();
                  $items[$k] = array("txt" => $item->getTitle(),
                      "selected" => $this->isFilterSelected($k));
              }

              include_once("./Services/UIComponent/CheckboxListOverlay/classes/class.ilCheckboxListOverlayGUI.php");
              $cb_over = new ilCheckboxListOverlayGUI("tbl_filters_".$this->getId());
              $cb_over->setLinkTitle($lng->txt("optional_filters"));
              $cb_over->setItems($items);

              $cb_over->setFormCmd($this->getParentCmd());
              $cb_over->setFieldVar("tblff".$this->getId());
              $cb_over->setHiddenVar("tblfsf".$this->getId());

              $cb_over->setSelectionHeaderClass("ilTableMenuItem");
              $this->tpl->setCurrentBlock("filter_select");

              // apply should be the first submit because of enter/return, inserting hidden submit
              $this->tpl->setVariable("HIDDEN_CMD_APPLY", $this->filter_cmd);

              $this->tpl->setVariable("FILTER_SELECTOR", $cb_over->getHTML());
              $this->tpl->parseCurrentBlock();
          }

          // if any filter
          if($ccnt > 0 || count($opt_filter) > 0)
          {
              $this->tpl->setVariable("TXT_FILTER", $lng->txt("filter"));

              if($ccnt > 0)
              {
                  if ($ccnt < $this->getFilterCols())
                  {
                      for($i = $ccnt; $i<=$this->getFilterCols(); $i++)
                      {
                          $this->tpl->touchBlock("filter_empty_cell");
                      }
                  }
                  $this->tpl->setCurrentBlock("filter_row");
                  $this->tpl->parseCurrentBlock();

                  $this->tpl->setCurrentBlock("filter_buttons");
                  $this->tpl->setVariable("CMD_APPLY", $this->filter_cmd);
                  $this->tpl->setVariable("TXT_APPLY", $lng->txt("apply_filter"));
                  $this->tpl->setVariable("CMD_RESET", $this->reset_cmd);
                  $this->tpl->setVariable("TXT_RESET", $lng->txt("reset_filter"));
              }
              else if(count($opt_filter) > 0)
              {
                  $this->tpl->setCurrentBlock("optional_filter_hint");
                  $this->tpl->setVariable('TXT_OPT_HINT', $lng->txt('optional_filter_hint'));
                  $this->tpl->parseCurrentBlock();
              }

              $this->tpl->setCurrentBlock("filter_section");
              $this->tpl->setVariable("FIL_ID", $this->getId());
              $this->tpl->parseCurrentBlock();
          }
    }

    //------------------------------------------------------------------------------------------------------------------
}