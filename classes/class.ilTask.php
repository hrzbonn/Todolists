<?php

class ilTask
{
    private $id;
    private $task;
    private $startdate;
    private $enddate;
    private $description;
    private $edit_status;
    private $created_by;
    private $updated_by;
    private $milestone_id;
    private $collect_list_link;
    private $button;


    function __construct()
    {
         $this->id=0;
         $this->task="";
         $this->startdate="";
         $this->enddate="";
         $this->description="";
         $this->edit_status="";
         $this->created_by="";
         $this->updated_by="";
         $this->milestone_id=0;
         $this->collect_list_link="";
         $this->button="";
    }

    function setButton($value)
    {
        $this->button=$value;
    }
    function getButton()
    {
        return $this->button;
    }
    function setCollectListLink($value)
    {
        $this->collect_list_link=$value;
    }
    function getCollectListLink()
    {
        return $this->collect_list_link;
    }
    function setId($value)
    {
        $this->id=$value;
    }
    function getId()
    {
        return $this->id;
    }
    function setTask($value)
    {
        $this->task=$value;
    }
    function getTask()
    {
        return $this->task;
    }
    function setStartdate($value)
    {
        $this->startdate=$value;
    }
    function getStartdate()
    {
        return $this->startdate;
    }
    function setEnddate($value)
    {
        $this->enddate=$value;
    }
    function getEnddate()
    {
        return $this->enddate;
    }
    function setDescription($value,$table_id,$counter)
    {
        if(strlen($value) > 100)
        {

            $pos = strripos($value," ");
            if($pos==false)$pos=100;
            $string=substr($value,0,100);
            $pos = strripos($string," ");
            if($pos==false)$pos=100;
            $string=substr($value,0,$pos+1);

            $in=str_replace(" ","_",$table_id.$counter);
            $in=str_replace("[","",$in);
            $in=str_replace("]","",$in);


            $link_mehr='<a onClick="(function(){
              document.getElementById(\'all_'.$in.'\').style.display= \'inline\';
              document.getElementById(\'weniger_'.$in.'\').style.display= \'none\';
            })();">...weiter lesen.</a>';
            
            $link_weniger='<a onClick="(function(){
              document.getElementById(\'all_'.$in.'\').style.display=  \'none\';
              document.getElementById(\'weniger_'.$in.'\').style.display= \'inline\';
            })();"> ...zuklappen.</a>';

            $string=$string.$link_mehr;
            $div="<div id = 'more_".$in."'>
                                <div id = 'weniger_".$in."'>".$string."</div>
                                <div id = 'all_".$in."'>".$value.$link_weniger."</div>
                              </div>";
            $css="<style>
                            #all_".$in."
                            {
                                display: none;
                            }
                        </style>";
            $this->description=$css.$div;
        }else $this->description=$value;
    }
    function getDescripiton()
    {
        return $this->description;
    }
    function setEditStatus($value)
    {
        $this->edit_status=$value;
    }
    function getEditStatus()
    {
        return $this->edit_status;
    }
    function setCreatedby($value)
    {
        $this->created_by=$value;
    }
    function getCreatedby()
    {
        return $this->created_by;
    }
    function setUpdatedby($value)
    {
        $this->updated_by=$value;
    }
    function getUpdatedby()
    {
        return $this->updated_by;
    }
    function setMilestoneId($value)
    {
        $this->milestone_id=$value;
    }
    function getMilestoneId()
    {
        return $this->milestone_id;
    }
}
?>
