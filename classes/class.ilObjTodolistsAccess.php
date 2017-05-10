<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2001 ILIAS open source, University of Cologne            |
	|                                                                             |
	| This program is free software; you can redistribute it and/or               |
	| modify it under the terms of the GNU General Public License                 |
	| as published by the Free Software Foundation; either version 2              |
	| of the License, or (at your option) any later version.                      |
	|                                                                             |
	| This program is distributed in the hope that it will be useful,             |
	| but WITHOUT ANY WARRANTY; without even the implied warranty of              |
	| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
	| GNU General Public License for more details.                                |
	|                                                                             |
	| You should have received a copy of the GNU General Public License           |
	| along with this program; if not, write to the Free Software                 |
	| Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
	+-----------------------------------------------------------------------------+
*/

include_once("./Services/Repository/classes/class.ilObjectPluginAccess.php");

/**
* Access/Condition checking for Example object
*
* Please do not create instances of large application classes (like ilObjExample)
* Write small methods within this class to determin the status.
*
* @author 		Alex Killing <alex.killing@gmx.de>
* @version $Id$
*/
class ilObjTodolistsAccess extends ilObjectPluginAccess
{

	/**
	 * Checks wether a user may invoke a command or not
	 * (this method is called by ilAccessHandler::checkAccess)
	 *
	 * Please do not check any preconditions handled by
	 * ilConditionHandler here. Also don't do usual RBAC checks.
	 *
	 * @param    string $a_cmd command (not permission!)
	 * @param    string $a_permission permission
	 * @param    int $a_ref_id reference id
	 * @param    int $a_obj_id object id
	 * @param    int $a_user_id user id (if not provided, current user is taken)
	 *
	 * @return    boolean        true, if everything is ok
	 */
	function _checkAccess($a_cmd, $a_permission, $a_ref_id, $a_obj_id, $a_user_id = "")
	{
		global $ilUser, $ilAccess;
		self::checkObjectOperationPermissionsInitialized();

		if ($a_user_id == "") {
			$a_user_id = $ilUser->getId();
		}

		global $objDefinition;
		if (isset($objDefinition->obj_data))
		{
			$objDefinition->obj_data['xtdo']['allow_copy'] = 1;
		}
		
		
		switch ($a_permission) {
			case "read":
				if (!ilObjTodolistsAccess::checkOnline($a_obj_id) &&
					!$ilAccess->checkAccessOfUser($a_user_id, "write", "", $a_ref_id)
				) {
					return false;
				}
				break;
		}

		return true;
	}




	/**
	 * Check online status of example object
	 */
	static function checkOnline($a_id)
	{
		global $ilDB;

		$set = $ilDB->query("SELECT is_online FROM rep_robj_xtdo_data " .
			" WHERE id = " . $ilDB->quote($a_id, "integer")
		);
		$rec = $ilDB->fetchAssoc($set);
		return (boolean)$rec["is_online"];
	}

	private static function checkObjectOperationPermissionsInitialized()
	{
		/**
		 * @var $ilDB ilDB
		 */
		global $ilDB;

		// lookup obj_id of xavc type definition
		$xtodo_typ_id = 0;
		$res = $ilDB->queryF("SELECT obj_id FROM object_data WHERE type = %s AND title = %s",
			array('text', 'text'), array('typ', 'xtdo'));
		while ($row = $ilDB->fetchObject($res)) {
			$xtodo_typ_id = (int)$row->obj_id;
			#break;
		}


		//check initialized permissions
		$check = $ilDB->queryF('SELECT ops_id FROM rbac_ta WHERE typ_id = %s',
			array('integer'), array($xtodo_typ_id));

		$init_ops = array();
		while ($row = $ilDB->fetchAssoc($check)) {
			$init_ops[] = $row['ops_id'];
		}


		//insert or update additional permissions for object type
		// general permissions: visible, read, write, delete, copy
		$xtodo_ops_ids = array();
		$res_1 = $ilDB->queryF('
				SELECT ops_id, operation FROM rbac_operations
				WHERE operation = %s
				OR operation = %s
				OR operation = %s
				OR operation = %s
				OR operation = %s
				OR operation = %s
				OR operation = %s',
			array('text', 'text', 'text', 'text', 'text', 'text', 'text'),
			array('visible', 'read', 'write', 'delete', 'edit_content', 'add_entry', 'edit_permission'));

		while ($row_1 = $ilDB->fetchAssoc($res_1)) {
			$xtodo_ops_ids[$row_1['operation']] = (int)$row_1['ops_id'];
		}

		foreach ($xtodo_ops_ids as $x_operation => $x_id) {
			if (!in_array($x_id, $init_ops)) {
				//insert missing operation
				$ilDB->insert('rbac_ta',
					array(
						'typ_id' => array('integer', $xtodo_typ_id),
						'ops_id' => array('integer', $x_id)
					));
			}
		}
		return $xtodo_typ_id;
	}
	
	
}

?>
