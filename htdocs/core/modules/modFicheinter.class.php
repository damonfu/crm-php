<?php

/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2011-2012 Herve Prot           <herve.prot@symeos.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 * 	\defgroup   ficheinter     Module intervention cards
 * 	\brief      Module to manage intervention cards
 * 	\file       htdocs/core/modules/modFicheinter.class.php
 * 	\ingroup    ficheinter
 * 	\brief      Fichier de description et activation du module Ficheinter
 */
include_once(DOL_DOCUMENT_ROOT . "/core/modules/DolibarrModules.class.php");

/**
 * 	\class      modFicheinter
 * 	\brief      Classe de description et activation du module Ficheinter
 */
class modFicheinter extends DolibarrModules {

	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	function modFicheinter($db) {
		global $conf;

		parent::__construct($db);
		$this->values->numero = 70;

		$this->values->family = "crm";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->values->name = preg_replace('/^mod/i', '', get_class($this));
		$this->values->description = "Gestion des fiches d'intervention";

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->values->version = 'dolibarr';

		$this->values->const_name = 'MAIN_MODULE_' . strtoupper($this->values->name);
		$this->values->special = 0;
		$this->values->picto = "intervention";

		// Data directories to create when module is enabled
		$this->values->dirs = array("/ficheinter/temp");

		// Dependencies
		$this->values->depends = array("modSociete");
		$this->values->requiredby = array();
		$this->values->conflictwith = array();
		$this->values->langfiles = array("bills", "companies", "interventions");

		// Config pages
		$this->values->config_page_url = array("fichinter.php");

		// Constantes
		$this->values->const = array();
		$r = 0;

		$this->values->const[$r][0] = "FICHEINTER_ADDON_PDF";
		$this->values->const[$r][1] = "chaine";
		$this->values->const[$r][2] = "soleil";
		$r++;

		$this->values->const[$r][0] = "FICHEINTER_ADDON";
		$this->values->const[$r][1] = "chaine";
		$this->values->const[$r][2] = "pacific";
		$r++;

		// Boites
		$this->values->boxes = array();

		// Permissions
		$this->values->rights = array();
		$this->values->rights_class = 'ficheinter';
		$r = 0;

		$r++;
		$this->values->rights[$r][0] = 61;
		$this->values->rights[$r][1] = 'Lire les fiches d\'intervention';
		$this->values->rights[$r][2] = 'r';
		$this->values->rights[$r][3] = 1;
		$this->values->rights[$r][4] = 'lire';

		$r++;
		$this->values->rights[$r][0] = 62;
		$this->values->rights[$r][1] = 'Creer/modifier les fiches d\'intervention';
		$this->values->rights[$r][2] = 'w';
		$this->values->rights[$r][3] = 0;
		$this->values->rights[$r][4] = 'creer';

		$r++;
		$this->values->rights[$r][0] = 64;
		$this->values->rights[$r][1] = 'Supprimer les fiches d\'intervention';
		$this->values->rights[$r][2] = 'd';
		$this->values->rights[$r][3] = 0;
		$this->values->rights[$r][4] = 'supprimer';

		$r++;
		$this->values->rights[$r][0] = 67;
		$this->values->rights[$r][1] = 'Exporter les fiches interventions';
		$this->values->rights[$r][2] = 'r';
		$this->values->rights[$r][3] = 0;
		$this->values->rights[$r][4] = 'export';

		$r++;
		$this->values->rights[$r][0] = 68;
		$this->values->rights[$r][1] = 'Envoyer les fiches d\'intervention par courriel';
		$this->values->rights[$r][2] = 'r';
		$this->values->rights[$r][3] = 0;
		$this->values->rights[$r][4] = 'ficheinter_advance';	  // Visible if option MAIN_USE_ADVANCED_PERMS is on
		$this->values->rights[$r][5] = 'send';

		//Exports
		//--------
		$r = 1;

		$this->values->export_code[$r] = $this->values->rights_class . '_' . $r;
		$this->values->export_label[$r] = 'InterventionCardsAndInterventionLines'; // Translation key (used only if key ExportDataset_xxx_z not found)
		$this->values->export_permission[$r] = array(array("ficheinter", "export"));
		$this->values->export_fields_array[$r] = array('s.rowid' => "IdCompany", 's.nom' => 'CompanyName', 's.address' => 'Address', 's.cp' => 'Zip', 's.ville' => 'Town', 's.fk_pays' => 'Country', 's.tel' => 'Phone', 's.siren' => 'ProfId1', 's.siret' => 'ProfId2', 's.ape' => 'ProfId3', 's.idprof4' => 'ProfId4', 's.code_compta' => 'CustomerAccountancyCode', 's.code_compta_fournisseur' => 'SupplierAccountancyCode', 'f.rowid' => "InterId", 'f.ref' => "InterRef", 'f.datec' => "InterDateCreation", 'f.duree' => "InterDuration", 'f.fk_statut' => 'InterStatus', 'f.description' => "InterNote", 'fd.rowid' => 'InterLineId', 'fd.date' => "InterLineDate", 'fd.duree' => "InterLineDuration", 'fd.description' => "InterLineDesc");
		$this->values->export_entities_array[$r] = array('s.rowid' => "company", 's.nom' => 'company', 's.address' => 'company', 's.cp' => 'company', 's.ville' => 'company', 's.fk_pays' => 'company', 's.tel' => 'company', 's.siren' => 'company', 's.siret' => 'company', 's.ape' => 'company', 's.idprof4' => 'company', 's.code_compta' => 'company', 's.code_compta_fournisseur' => 'company', 'f.rowid' => "intervention", 'f.ref' => "intervention", 'f.datec' => "intervention", 'f.duree' => "intervention", 'f.fk_statut' => "intervention", 'f.description' => "intervention", 'fd.rowid' => "inter_line", 'fd.date' => "inter_line", 'fd.duree' => 'inter_line', 'fd.description' => 'inter_line');

		$this->values->export_sql_start[$r] = 'SELECT DISTINCT ';
		$this->values->export_sql_end[$r] = ' FROM (' . MAIN_DB_PREFIX . 'fichinter as f, ' . MAIN_DB_PREFIX . 'fichinterdet as fd, ' . MAIN_DB_PREFIX . 'societe as s)';
		$this->values->export_sql_end[$r] .=' WHERE f.fk_soc = s.rowid AND f.rowid = fd.fk_fichinter';
		$this->values->export_sql_end[$r] .=' AND f.entity = ' . $conf->entity;
		$r++;
	}

	/**
	 * 		Function called when module is enabled.
	 * 		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 * 		It also creates data directories
	 *
	 *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
	 */
	function init($options = '') {
		global $conf;

		// Permissions
		$this->values->remove($options);

		$sql = array(
			"DELETE FROM " . MAIN_DB_PREFIX . "document_model WHERE nom = '" . $this->values->const[0][2] . "' AND entity = " . $conf->entity,
			"INSERT INTO " . MAIN_DB_PREFIX . "document_model (nom, type, entity) VALUES('" . $this->values->const[0][2] . "','ficheinter'," . $conf->entity . ")",
		);

		return $this->values->_init($sql, $options);
	}

	/**
	 * 		Function called when module is disabled.
	 *      Remove from database constants, boxes and permissions from Dolibarr database.
	 * 		Data directories are not deleted
	 *
	 *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
	 */
	function remove($options = '') {
		$sql = array();

		return $this->values->_remove($sql, $options);
	}

}

?>