<?php
/* Copyright (C) 2004-2014  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2008       Raphael Bertrand	<raphael.bertrand@resultic.fr>
 * Copyright (C) 2010-2013	Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2012      	Christophe Battarel <christophe.battarel@altairis.fr>
 * Copyright (C) 2012       Cedric Salvador     <csalvador@gpcsolutions.fr>
 * Copyright (C) 2015       Marcos García       <marcosgdf@gmail.com>
 * Copyright (C) 2017-2018  Ferran Marcet       <fmarcet@2byte.es>
 * Copyright (C) 2018       Frédéric France     <frederic.france@netlogic.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 *	\file       htdocs/core/modules/operationorder/doc/pdf_olaf.modules.php
 *	\ingroup    operationorder
 *	\brief      File of Class to generate PDF orders with template olaf
 */

require_once __DIR__.'/../../modules_operationorder.php';
require_once __DIR__.'/../../../../../class/operationorder.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/commondocgenerator.class.php';


/**
 *	Class to generate PDF orders with template Eratosthene
 */
class pdf_barcodeimp extends CommonDocGenerator
{
	/**
	 * @var DoliDb Database handler
	 */
	public $db;

	/**
	 * @var string model name
	 */
	public $name;

	/**
	 * @var string model description (short text)
	 */
	public $description;

	/**
	 * @var int 	Save the name of generated file as the main doc when generating a doc with this template
	 */
	public $update_main_doc_field;

	/**
	 * @var string document type
	 */
	public $type;

	/**
	 * @var array Minimum version of PHP required by module.
	 * e.g.: PHP ≥ 5.5 = array(5, 5)
	 */
	public $phpmin = array(5, 5);

	/**
	 * Dolibarr version of the loaded document
	 * @var string
	 */
	public $version = 'dolibarr';

	/**
	 * @var int page_largeur
	 */
	public $page_largeur;

	/**
	 * @var int page_hauteur
	 */
	public $page_hauteur;

	/**
	 * @var array format
	 */
	public $format;

	/**
	 * @var int marge_gauche
	 */
	public $marge_gauche;

	/**
	 * @var int marge_droite
	 */
	public $marge_droite;

	/**
	 * @var int marge_haute
	 */
	public $marge_haute;

	/**
	 * @var int marge_basse
	 */
	public $marge_basse;

	/**
	 * Issuer
	 * @var Societe
	 */
	public $emetteur;


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $conf, $langs, $mysoc;

		// Translations
		$langs->loadLangs(array("main"));

		$this->db = $db;
		$this->name = "barcodeimp";
		$this->description = $langs->trans('PDFbarcodeimpdescription');
		$this->update_main_doc_field = 1;		// Save the name of generated file as the main doc when generating a doc with this template

		// Dimension page
		$this->type = 'pdf';
		$formatarray=pdf_getFormat();
		$this->page_largeur = $formatarray['width'];
		$this->page_hauteur = $formatarray['height'];
		$this->format = array($this->page_largeur,$this->page_hauteur);
		$this->marge_gauche=isset($conf->global->MAIN_PDF_MARGIN_LEFT)?$conf->global->MAIN_PDF_MARGIN_LEFT:10;
		$this->marge_droite=isset($conf->global->MAIN_PDF_MARGIN_RIGHT)?$conf->global->MAIN_PDF_MARGIN_RIGHT:10;
		$this->marge_haute =isset($conf->global->MAIN_PDF_MARGIN_TOP)?$conf->global->MAIN_PDF_MARGIN_TOP:10;
		$this->marge_basse =isset($conf->global->MAIN_PDF_MARGIN_BOTTOM)?$conf->global->MAIN_PDF_MARGIN_BOTTOM:10;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Function to build pdf onto disk
	 *
	 *  @param		Object		$object				Object to generate
	 *  @param		Translate	$outputlangs		Lang output object
	 *  @param		string		$srctemplatepath	Full path of source filename for generator using a template file
	 *  @param		int			$hidedetails		Do not show line details
	 *  @param		int			$hidedesc			Do not show desc
	 *  @param		int			$hideref			Do not show ref
	 *  @return     int             			    1=OK, 0=KO
	 */
	public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
    {
        // phpcs:enable
        global $user, $langs, $conf, $db;

        /**
         * @var  $object OperationOrder
         */

        if (! is_object($outputlangs)) $outputlangs=$langs;
        // For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
        if (! empty($conf->global->MAIN_USE_FPDF)) $outputlangs->charset_output='ISO-8859-1';

        // Translations
        $outputlangs->loadLangs(array("main"));

        $hideref = 1; // force hidden ref

        $hidetop=0;
        if(!empty($conf->global->MAIN_PDF_DISABLE_COL_HEAD_TITLE)){
            $hidetop=$conf->global->MAIN_PDF_DISABLE_COL_HEAD_TITLE;
        }

        $hidetopNewPage = $hidetop;

        if ($conf->operationorder->dir_output)
        {

            $dir = $conf->operationorder->multidir_output[$object->entity];
            $file = $dir . "/barcodeimp_list.pdf";
			if ($hidedetails) $file = $dir . "/barcodeusr_list.pdf";

            if (! file_exists($dir))
            {
                if (dol_mkdir($dir) < 0)
                {
                    $this->error=$langs->transnoentities("ErrorCanNotCreateDir", $dir);
                    return 0;
                }
            }

            if (file_exists($dir))
            {
                // Create pdf instance
                $pdf = pdf_getInstance($this->format);
                $default_font_size = pdf_getPDFFontSize($outputlangs); // Must be after pdf_getInstance
                $pdf->SetAutoPageBreak(1, 0);

                $heightforinfotot = 0; // Height reserved to output the info and total part
                $heightforfreetext = (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT) ? $conf->global->MAIN_PDF_FREETEXT_HEIGHT : 5); // Height reserved to output the free text on last page
                $heightforfooter = $this->marge_basse + (empty($conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS) ? 5 : 10); // Height reserved to output the footer (value include bottom margin)

                if (class_exists('TCPDF'))
                {
                    $pdf->setPrintHeader(false);
                    $pdf->setPrintFooter(false);
                }
                $pdf->SetFont(pdf_getPDFFont($outputlangs));
                // Set path to the background PDF File
                if (! empty($conf->global->MAIN_ADD_PDF_BACKGROUND))
                {
                    $pagecount = $pdf->setSourceFile($conf->mycompany->multidir_output[$object->entity].'/'.$conf->global->MAIN_ADD_PDF_BACKGROUND);
                    $tplidx = $pdf->importPage(1);
                }

                $pdf->Open();

                $pagenb=0;

                $pdf->SetTitle($outputlangs->transnoentities('PdfBarcodeImpTitle'));
                $pdf->SetSubject($outputlangs->transnoentities("PdfBarcodeImpTitle"));
                $pdf->SetCreator("Dolibarr ".DOL_VERSION);
                $pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
                $pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("PdfOrderTitle")." ".$outputlangs->convToOutputCharset($object->thirdparty->name));
                if (!empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) $pdf->SetCompression(false);

                $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite); // Left, Top, Right

                // New page
                $pdf->AddPage();
                if (! empty($tplidx)) $pdf->useTemplate($tplidx);
                $pagenb++;
                $pdf->SetFont('', '', $default_font_size - 1);
                $pdf->MultiCell(0, 3, '');		// Set interline to 3
                $pdf->SetTextColor(0, 0, 0);

                $posy=$this->marge_haute;
                $posx=$this->page_largeur-$this->marge_droite-100;
                $pdf->SetXY($this->marge_gauche, $posy);


                //Code-barres de type "improductif"
                $barCodeHeight = 10;
                $style= array(
                    'text'=> true,
                    'fontsize'=>4
                );

                if ($hidedetails)
				{
					$object->list = array();

					dol_include_once('/operationorder/class/usergroupoperationorder.class.php');
					$userGroup = new UserGroupOperationOrder($this->db);
					$retgroup = $userGroup->fetch($conf->global->OPERATION_ORDER_GROUPUSER_DEFAULTPLANNING);
					if ($retgroup > 0)
					{
						$userList = $userGroup->listUsersForGroup();
						if (!empty($userList))
						{
							foreach ($userList as $u)
							{
								$object->list['USR'.$u->login] = $u->getFullname($langs);
							}
						}
					}
				}

                foreach($object->list as $idbarcode=>$label)
                {
					if (! $hidedetails)
					{
						$operationorderbarcode = new OperationOrderBarCode($db);
						if ($idbarcode == 'annul' || $idbarcode == 'fin')
						{
							$operationorderbarcode->code = 'IMPAnnul';
							$operationorderbarcode->label = "Annuler";

							if ($idbarcode == 'fin')
							{
								$operationorderbarcode->code = 'IMPFin';
								$operationorderbarcode->label = "Fin de journée";
							}
						}
						else $res =$operationorderbarcode->fetch($idbarcode);
					}
					else
					{
						$operationorderbarcode = new stdClass;
						$operationorderbarcode->code = $idbarcode;
						$operationorderbarcode->label = $label;
					}

                    $pdf->startTransaction();

                    $pdf->SetXY($this->marge_gauche, $posy);
                    $pdf->MultiCell(100, 3, $operationorderbarcode->label, '', 'C');
                    $pdf->write1DBarcode($operationorderbarcode->code, 'C128', $this->marge_gauche + 100, $posy, 50, $barCodeHeight, '', $style);
                    $posy = $posy + $barCodeHeight + 20;
                    $posyend = $pdf->GetY();

                    if ($posyend > $this->page_hauteur - $heightforfooter)
                    {
                        $pdf->rollbackTransaction(true);

                        $pdf->AddPage();
                        $pagenb++;

                        $posy=$this->marge_haute;
                        $pdf->SetXY($this->marge_gauche, $posy);
                        $pdf->MultiCell(100, 3, $operationorderbarcode->label, '', 'C');
                        $pdf->write1DBarcode($operationorderbarcode->code, 'C128', $this->marge_gauche + 100, $posy, 50, $barCodeHeight, '', $style);
                        $posy = $posy + $barCodeHeight + 20;

                    } else {
                        $pdf->commitTransaction();
                    }

                }

                // Pied de page
                if (method_exists($pdf, 'AliasNbPages')) $pdf->AliasNbPages();

                $pdf->Close();

                $pdf->Output($file, 'F');

                if (! empty($conf->global->MAIN_UMASK))
                    @chmod($file, octdec($conf->global->MAIN_UMASK));

                $this->result = array('fullpath'=>$file);

                return 1;   // No error
            }
            else
            {
                $this->error=$langs->transnoentities("ErrorCanNotCreateDir", $dir);
                return 0;
            }
        }
        else
        {
            $this->error=$langs->transnoentities("ErrorConstantNotDefined", "COMMANDE_OUTPUTDIR");
            return 0;
        }
    }

}
