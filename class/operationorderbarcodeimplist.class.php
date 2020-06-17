<?php

if (!class_exists('SeedObject'))
{

    define('INC_FROM_DOLIBARR', true);
    require_once dirname(__FILE__).'/../config.php';
}


class OperationOrderBarCodeImpList extends SeedObject
{

    /** @var string $element Name of the element (tip for better integration in Dolibarr: this value should be the reflection of the class name with ucfirst() function) */
    public $element = 'operationorderbarcodeimplist';

    /** @var int $ismultientitymanaged 0=No test on entity, 1=Test with field entity, 2=Test with link by societe */
    public $ismultientitymanaged = 1;

    /** @var string $picto a picture file in [@...]/img/object_[...@].png  */
    public $picto = 'operationorder@operationorder';

    public $list;
    public $entity;

    /**
     * OperationOrder constructor.
     * @param DoliDB    $db    Database connector
     */
    public function __construct($db)
    {
        global $conf;

        dol_include_once('/operationorder/class/operationorderbarcode.class.php');

        parent::__construct($db);

        $this->init();

        $TBarCodeList = array();
        $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."operationorderbarcode WHERE code LIKE '%IMP%' AND entity = '". $conf->entity . "'";

        $resql = $db->query($sql);

        if($resql){

            $barcode = new OperationOrderBarCode($db);

            while($obj = $db->fetch_object($resql)){

                $res = $barcode->fetch($obj->rowid);

                if($res > 0) $TBarCodeList[] = $barcode;
            }
        }

        $this->list = $TBarCodeList;



        $this->entity = $conf->entity;
    }

    /**
     *  Create a document onto disk according to template module.
     *
     *  @param	    string		$modele			Force template to use ('' to not force)
     *  @param		Translate	$outputlangs	objet lang a utiliser pour traduction
     *  @param      int			$hidedetails    Hide details of lines
     *  @param      int			$hidedesc       Hide description
     *  @param      int			$hideref        Hide ref
     *  @param      null|array  $moreparams     Array to provide more information
     *  @return     int         				0 if KO, 1 if OK
     */
    public function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = null)
    {
        global $conf, $langs;

        $langs->load("operationorder@operationorder");

        $modele = "barcodeimp";

        $modelpath = "core/modules/operationorder/barcode/doc/";

        return $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
    }

}
