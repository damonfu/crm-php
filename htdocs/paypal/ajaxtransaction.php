<?php
/* Copyright (C) 2011 Regis Houssin  <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *       \file       htdocs/paypal/ajaxtransactiondetails.php
 *       \brief      File to return Ajax response on paypal transaction details
 *       \version    $Id$
 */

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL',1); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
if (! defined('NOCSRFCHECK'))    define('NOCSRFCHECK','1');

require('../main.inc.php');
require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT.'/paypal/lib/paypal.lib.php');
require_once(DOL_DOCUMENT_ROOT."/paypal/lib/paypalfunctions.lib.php");

$langs->load('main');
$langs->load('users');
$langs->load('companies');


/*
 * View
 */

// Ajout directives pour resoudre bug IE
//header('Cache-Control: Public, must-revalidate');
//header('Pragma: public');

//top_htmlhead("", "", 1);  // Replaced with top_httphead. An ajax page does not need html header.
top_httphead();

//echo '<!-- Ajax page called with url '.$_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"].' -->'."\n";

//echo '<body class="nocellnopadd">'."\n";

dol_syslog(join(',',$_GET));

if (isset($_GET['action']) && ! empty($_GET['action']) && isset($_GET['transaction_id']) && ! empty($_GET['transaction_id']) )
{
	if ($_GET['action'] == 'add')
	{
		$soc = new Societe($db);
		
		// Create customer if not exists
		$ret = $soc->fetchObjectFromRefExt($soc->table_element,$_SESSION[$_GET['transaction_id']]['PAYERID']);
		if ($ret < 0)
		{
			// Load object modCodeTiers
			$module=$conf->global->SOCIETE_CODECLIENT_ADDON;
			if (! $module) dolibarr_error('',$langs->trans("ErrorModuleThirdPartyCodeInCompanyModuleNotDefined"));
			if (substr($module, 0, 15) == 'mod_codeclient_' && substr($module, -3) == 'php')
			{
				$module = substr($module, 0, dol_strlen($module)-4);
			}
			require_once(DOL_DOCUMENT_ROOT ."/includes/modules/societe/".$module.".php");
			$modCodeClient = new $module;
			
			// Create customer and return rowid
			$soc->ref_ext			= $_SESSION[$_GET['transaction_id']]['PAYERID'];
			$soc->name              = empty($conf->global->MAIN_FIRSTNAME_NAME_POSITION)?trim($_SESSION[$_GET['transaction_id']]['FIRSTNAME'].' '.$_SESSION[$_GET['transaction_id']]['LASTNAME']):trim($_SESSION[$_GET['transaction_id']]['LASTNAME'].' '.$_SESSION[$_GET['transaction_id']]['FIRSTNAME']);
			$soc->nom_particulier	= $_SESSION[$_GET['transaction_id']]['LASTNAME'];
			$soc->prenom			= $_SESSION[$_GET['transaction_id']]['FIRSTNAME'];
			$soc->address			= $_SESSION[$_GET['transaction_id']]['SHIPTOSTREET'];
			$soc->zip				= $_SESSION[$_GET['transaction_id']]['SHIPTOZIP'];
			$soc->town				= $_SESSION[$_GET['transaction_id']]['SHIPTOCITY'];
			//$soc->pays_id			= $_POST["pays_id"];
			$soc->email				= $_SESSION[$_GET['transaction_id']]['EMAIL'];
			$soc->code_client		= ($modCodeClient->code_auto ? $modCodeClient->getNextValue($soc,0):'');
			$soc->tva_assuj			= 1;
			$soc->client			= 1;
			$soc->particulier		= 1;
			
			$db->begin();
			$result = $soc->create($user);
			if ($result >= 0)
			{
				if ($soc->particulier)
				{
					$contact=new Contact($db);
					
					$contact->civilite_id = $soc->civilite_id;
					$contact->name=$soc->nom_particulier;
					$contact->firstname=$soc->prenom;
					$contact->address=$soc->address;
					$contact->zip=$soc->zip;
					$contact->cp=$soc->cp;
					$contact->town=$soc->town;
					$contact->ville=$soc->ville;
					$contact->fk_pays=$soc->fk_pays;
					$contact->socid=$soc->id;
					$contact->status=1;
					$contact->email=$soc->email;
					$contact->priv=0;
					
					$result=$contact->create($user);
				}
			}
			
			if ($result >= 0)
			{
				$db->commit();
			}
			else
			{
				$db->rollback();
				$langs->load("errors");
				echo $langs->trans($contact->error);
				echo $langs->trans($soc->error);
			}
		}
		
		// Add element (order, bill, etc.)
		if ($soc->id > 0 && isset($_GET['element']) && ! empty($_GET['element']))
		{
			$error=0;
			
			// Parse element/subelement (ex: project_task)
	        $element = $subelement = $_GET['element'];
	        if (preg_match('/^([^_]+)_([^_]+)/i',$_GET['element'],$regs))
	        {
	            $element = $regs[1];
	            $subelement = $regs[2];
	        }
	        // For compatibility
            if ($element == 'order') { $element = $subelement = 'commande'; }

            dol_include_once('/'.$element.'/class/'.$subelement.'.class.php');

            $classname = ucfirst($subelement);
            $object = new $classname($db);
            
            $object->socid=$soc->id;
            $object->fetch_thirdparty();
            
            $db->begin();
            
            $object->date		= dol_now();
            $object->ref_ext	= $_SESSION[$_GET['transaction_id']]['SHIPTOCITY'];
            
            $object_id = $object->create($user);
            if ($object_id > 0)
            {
	            $i=0;
				
	            // Add element lines
	            while (isset($_SESSION[$_GET['transaction_id']]["L_NAME".$i]))
				{
					$product = new Product($db);
					$ret = $product->fetch('',$_SESSION[$_GET['transaction_id']]["L_NUMBER".$i]);

					if ($ret > 0)
					{
						$product_type=($product->product_type?$product->product_type:0);
						
						$result = $object->addline(
								                    $object_id,
								                    $product->description,
								                    $product->price,
								                    $_SESSION[$_GET['transaction_id']]["L_QTY".$i],
								                    $product->tva_tx,
								                    $product->localtax1_tx,
								                    $product->localtax2_tx,
								                    $product->id,
								                    0,
								                    0,
								                    0,
								                    'HT',
								                    0,
								                    '',
								                    '',
								                    $product_type
								                    );
	
	                    if ($result < 0)
	                    {
	                        $error++;
	                        break;
	                    }
					}
					
					$i++;
				}
				
				// Insert default contacts
				/*
			    if ($contact->id > 0)
			    {
			        $result=$object->add_contact($contact->id,'CUSTOMER','external');
			        if ($result < 0) $error++;
			    }
			    */
            }
            else
            {
            	$error++;
            }
            
            if ($object_id > 0 && ! $error)
		    {
		        $db->commit();
		    }
		    else
		    {
		        $db->rollback();
		    }
		}

		// Return element id
		echo $object->getNomUrl(0,0,1);
		
		/*
		foreach ($_SESSION[$_GET['transaction_id']] as $key => $value)
		{
			echo $key.': '.$value.'<br />';
		}
		*/
		
	}
	else if ($_GET['action'] == 'showdetails')
	{
		// For paypal request optimization
		if (! isset($_SESSION[$_GET['transaction_id']]) ) $_SESSION[$_GET['transaction_id']] = GetTransactionDetails($_GET['transaction_id']);
		
		$var=true;
		
		echo '<table style="noboardernopading" width="100%">';
		echo '<tr class="liste_titre">';
		echo '<td colspan="2">'.$langs->trans('CustomerDetails').'</td>';
		echo '</tr>';
		
		$var=!$var;
		echo '<tr '.$bc[$var].'><td>'.$langs->trans('LastName').': </td><td>'.$_SESSION[$_GET['transaction_id']]['LASTNAME'].'</td></tr>';
		$var=!$var;
		echo '<tr '.$bc[$var].'><td>'.$langs->trans('FirstName').': </td><td>'.$_SESSION[$_GET['transaction_id']]['FIRSTNAME'].'</td></tr>';
		$var=!$var;
		echo '<tr '.$bc[$var].'><td>'.$langs->trans('Address').': </td><td>'.$_SESSION[$_GET['transaction_id']]['SHIPTOSTREET'].'</td></tr>';
		$var=!$var;
		echo '<tr '.$bc[$var].'><td>'.$langs->trans('Zip').' / '.$langs->trans('Town').': </td><td>'.$_SESSION[$_GET['transaction_id']]['SHIPTOZIP'].' '.$_SESSION[$_GET['transaction_id']]['SHIPTOCITY'].'</td></tr>';
		$var=!$var;
		echo '<tr '.$bc[$var].'><td>'.$langs->trans('Country').': </td><td>'.$_SESSION[$_GET['transaction_id']]['SHIPTOCOUNTRYNAME'].'</td></tr>';
		$var=!$var;
		echo '<tr '.$bc[$var].'><td>'.$langs->trans('Email').': </td><td>'.$_SESSION[$_GET['transaction_id']]['EMAIL'].'</td>';
		$var=!$var;
		echo '<tr '.$bc[$var].'><td>'.$langs->trans('Date').': </td><td>'.dol_print_date(dol_stringtotime($_SESSION[$_GET['transaction_id']]['ORDERTIME']),'dayhour').'</td>';
		
		echo '</table>';
		
		$i=0;
		
		echo '<table style="noboardernopading" width="100%">';
		
		echo '<tr class="liste_titre">';
		echo '<td>'.$langs->trans('Ref').'</td>';
		echo '<td>'.$langs->trans('Label').'</td>';
		echo '<td>'.$langs->trans('Qty').'</td>';
		echo '</tr>';
		
		while (isset($_SESSION[$_GET['transaction_id']]["L_NAME".$i]))
		{
			$var=!$var;
			
			echo '<tr '.$bc[$var].'>';
			echo '<td>'.$_SESSION[$_GET['transaction_id']]["L_NUMBER".$i].'</td>';
			echo '<td>'.$_SESSION[$_GET['transaction_id']]["L_NAME".$i].'</td>';
			echo '<td>'.$_SESSION[$_GET['transaction_id']]["L_QTY".$i].'</td>';
			echo '</tr>';
			
			$i++;
		}
		
		echo '</table>';
/*		
		echo '<br />';
		
		foreach ($_SESSION[$_GET['transaction_id']] as $key => $value)
		{
			echo $key.': '.$value.'<br />';
		}
*/
	}
}

//echo "</body>";
//echo "</html>";
?>