<?php

include_once("./Services/Repository/classes/class.ilRepositoryObjectPlugin.php");
 
/**
* Example repository object plugin
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
*/
class ilTodolistsPlugin extends ilRepositoryObjectPlugin
{
	function getPluginName()
	{
		return "Todolists";
	}
	final protected function uninstallCustom()
	{
		global $ilDB;
		$mySqlString="DROP TABLE IF EXISTS  rep_robj_xtdo_data";
		$ilDB->query($mySqlString);
		$mySqlString="DROP TABLE IF EXISTS  rep_robj_xtdo_tasks";
		$ilDB->query($mySqlString);
		$mySqlString="DROP TABLE IF EXISTS  rep_robj_xtdo_milsto";
		$ilDB->query($mySqlString);
	}
}
?>
