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
	protected function afterActivation()
	{
		global $ilDB;
		$xtodo_typ_id = 0;
		$res = $ilDB->queryF("SELECT obj_id FROM object_data WHERE type = %s AND title = %s",
			array('text', 'text'), array('typ', 'xtdo'));
		while ($row = $ilDB->fetchObject($res)) {
			$xtodo_typ_id = (int)$row->obj_id;
		}
		
		$check = $ilDB->queryF('SELECT ops_id FROM rbac_ta WHERE typ_id = %s',
			array('integer'), array($xtodo_typ_id));
		
		$init_ops = array();
		while ($row = $ilDB->fetchAssoc($check)) {
			$init_ops[] = $row['ops_id'];
		}
		
		$xtodo_ops_ids = array();
		$res_1 = $ilDB->queryF('
				SELECT ops_id, operation FROM rbac_operations
				WHERE operation = %s
				OR operation = %s
				OR operation = %s
				OR operation = %s
				OR operation = %s
				OR operation = %s
				OR operation = %s
				OR operation = %s',
			array('text', 'text', 'text', 'text','text', 'text', 'text', 'text'),
			array('visible', 'read', 'write','copy', 'delete', 'edit_content', 'add_entry', 'edit_permission'));
		
		while ($row_1 = $ilDB->fetchAssoc($res_1)) {
			$xtodo_ops_ids[$row_1['operation']] = (int)$row_1['ops_id'];
		}
		
		foreach ($xtodo_ops_ids as $x_operation => $x_id) {
			if (!in_array($x_id, $init_ops)) {
				$ilDB->insert('rbac_ta',
					array(
						'typ_id' => array('integer', $xtodo_typ_id),
						'ops_id' => array('integer', $x_id)
					));
			}
		}
	}
}
?>
