ALTER TABLE llx_operationorderaction ADD INDEX idx_operationorderaction_date (dated,datef);
ALTER TABLE llx_operationorderaction ADD CONSTRAINT llx_operationorderaction_ibfk_1 FOREIGN KEY (fk_operationorder) REFERENCES llx_operationorder (rowid) ON DELETE CASCADE;
