ALTER TABLE `llx_operationorder_status_usergroup_rights` ADD UNIQUE( `fk_operationorderstatus`, `code`, `fk_usergroup`);
