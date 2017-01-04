<?php
 /**
  * szavazok model
  * taskok: szavazok, szavazatEdit, szavazatDelete, eredmeny, szavazatSave
  * Licensz: GNU/GPL
  * Szerz�: Fogler Tibor   tibor.fogler@gmail.com_addref
  * web: github.com/utopszkij/elovalasztok2018
  * Verzi�: V1.00  2016.09.14.
  * Adat t�rol�s:   szavaz�s: #__category (params a metadata mez�ben JSON form�ban) MINIMUM:{"status":"vita1"}
  *                 alternativ�k #__content
  *
  */

include_once JPATH_SITE.'/components/com_pvoks/accescontrol.php';  
include_once JPATH_SITE.'/components/com_pvoks/funkciok.php';  
  
class pvoksModelPvoks extends JModelList {
	private $errorMsg = '';
	
	/** { 
	*		"status":"vita1",  								("vita0","vita1","vita2","szavazas","lezart"....)
	*       "steps":["vita0":["usergr1":["akcio1","akcio2"...],"usergr2:["akcio1",'akcio2"....]...],
	*                 ["vita1":["usergr1":["akcio1","akcio2"...],"usergr2:["akcio1",'akcio2"....]...]...
	*       "auto":{"felt�tel":"algoritmus" ...}
	*       ]
	*	}
	*/	
	private $defSzavazasParams;
	
	function __construct() {
		parent::__construct();
		
		$this->defSzavazasParams = JSON_encode('{
			"steps":["vita0:":["guest":["view-suggestion","view-alternative","view-creator","view-comment","comment"],
			                   "registered":["power"],
							   "admin":["setStep"]],
			         "vita1":["guest":["view-suggestion","view-alternative","view-creator","view-comment","comment"],
			                   "registered":["comment"]],
							   
			]
		}');
		
		$db = JFactory::getDBO();
		$db->setQuery('CREATE TABLE IF NOT EXISTS #__szavazatok (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `temakor_id` int(11) NOT NULL COMMENT "t�mak�r azonos�t�",
		  `szavazas_id` int(11) NOT NULL COMMENT "szavaz�s azonos�t�",
		  `szavazo_id` int(11) NOT NULL COMMENT "szava� azonos�t� a concorde-shulze ki�rt�kel�shez",
		  `user_id` int(11) NOT NULL COMMENT "Ha nyilt szavaz�s a szavaz� user_id -je",
		  `alternativa_id` int(11) NOT NULL COMMENT "alternativa azonosit�",
		  `pozicio` int(11) NOT NULL COMMENT "ebbe a pozici�ba sorolta az adott alternativ�t",
		  `fordulo` tinyint NOT NULL DEFAULT 0 COMMENT "szavaz�si fordul�",
		  `ada0` tinyint NOT NULL DEFAULT 0 COMMENT "ADA regisztr�lt",
		  `ada1` tinyint NOT NULL DEFAULT 0 COMMENT "ADA szem.adatokat megadta",
		  `ada2` tinyint NOT NULL DEFAULT 0 COMMENT "ADA email ellen�rz�tt",
		  `ada3` tinyint NOT NULL DEFAULT 0 COMMENT "ADA hiteles",
		  PRIMARY KEY (`id`),
		  KEY `temakori` (`temakor_id`),
		  KEY `szavazasi` (`szavazas_id`),
		  KEY `useri` (`user_id`),
		  KEY `szavazoi` (`szavazo_id`)
		)');
		$db->query();
		
		$db->setQuery('CREATE TABLE IF NOT EXISTS #__eredmeny (
			  `organization` int(11) NOT NULL DEFAULT 0 COMMENT "t�mak�r ID",
			  `pollid` int(11) NOT NULL DEFAULT 0 COMMENT "szavaz�s ID",
			  `vote_count` int(11) NOT NULL DEFAULT 0 COMMENT "szavazatok sz�ma",
			  `report` text COMMENT "cachelt report htm k�d",
			  `filter` varchar(128) NOT NULL DEFAULT "" COMMENT "szavazatok rekordra vonatkozo sql filter alias:a",
			  `fordulo` tinyint NOT NULL DEFAULT 0 COMMENT "szavaz�si fordul�",
			  `c1` int(11) NOT NULL DEFAULT 0 COMMENT "condorce els� helyezet alertativa ID",
			  `c2` int(11) NOT NULL DEFAULT 0 COMMENT "condorce m�sodik helyezet alertativa ID",
			  `c3` int(11) NOT NULL DEFAULT 0 COMMENT "condorce harmadik helyezet alertativa ID",
			  `c4` int(11) NOT NULL DEFAULT 0 COMMENT "condorce negyedik helyezet alertativa ID",
			  `c5` int(11) NOT NULL DEFAULT 0 COMMENT "condorce �t�dik helyezet alertativa ID",
			  `c6` int(11) NOT NULL DEFAULT 0 COMMENT "condorce hatodik helyezet alertativa ID",
			  `c7` int(11) NOT NULL DEFAULT 0 COMMENT "condorce hetedik helyezet alertativa ID",
			  `c8` int(11) NOT NULL DEFAULT 0 COMMENT "condorce nyolcadik helyezet alertativa ID",
			  `c9` int(11) NOT NULL DEFAULT 0 COMMENT "condorce kilencedik helyezet alertativa ID",
			  `c10` int(11) NOT NULL DEFAULT 0 COMMENT "condorce tizedik helyezet alertativa ID"
			)
		');
		$db->query();	
	}
	
	/**
	  * egy adott szavaz�s adatainak beolvas�sa
	  * @param integer szavazas_id
	  * @return {"id":sz�m, "nev":string, "leiras":string, "params":object, "alternativak":[{"id":sz�m,"nev":string},....]} | false
	*/  
	public function getSzavazas($szavazas_id) {
		$db = JFactory::getDBO();
		$result = new stdClass();
		$result->oevkId = $szavazas_id;
		$result->oevkNev = '';
		$result->alternativak = array();
		$db->setQuery('select * from #__categories where id='.$db->quote($szavazas_id));
		$res = $db->loadObject(); 
		if ($res) {
			$result->id = $res->id;
			$result->nev = $res->title;
			$result->leiras = $res->description;
			$w = $res->metadata;
			if ($w == '') {
				// nem szavaz�s
				$resul = false;
				return $result;
			} else {
				$result->params = new stdClass();
				$w = JSON_encode($w);
				foreach ($this->defSzavazasParams as $fn => $fv) {
					if (isset($w->$fn))
						$result->params->$fn = $w->$fn;
					else
						$result->params->$fn = $fv;
				}
			}
			$db->setQuery('select *
			from #__content
			where catid = '.$db->quote($szavazas_id).' and metadata like "%alternativa%"
			order by title');
			$res = $db->loadObjectList();
			foreach ($res as $res1) {
				$w = new stdClass();
				$w->id = $res1->id;
				$w->nev = $res1->title;
				$result->alternativak[] = $w;
			}
		}
		return $result;
	}
	
	/**
	  * get szavazas_id alternativa_id alapj�n
	  * @param integer alternativa_id
	  * @return integer szavazas_id
	  */
	public function getSzavazasFromAlternativa($altId,$config) {
		$db = JFactory::getDBO();
		$result = 0;
		$db->setQuery('select * from #__content where id='.$db->quote($altId));
		$res = $db->loadObject();
		return $res->catid;
	}
	
	public function getErrorMsg() {
	  return $this->errorMsg;	
	}
	
	/**
	  * szavazat t�rol�sa adatb�zisba
	  * @param integer oevk id
	  * @param string jelolt_id=pozicio,....
	  * @param JUser
	  * @param integer fordulo
	  * @return boolean
	*/  
	public function save($szavazas_id, $szavazat, $user, $fordulo) {
		$result = true;
		$msg = '';
		if (teheti($szavazas_id, $user, 'szavazas', $msg) == false) {
			  $this->errorMsg .= $msg;
			  $result = false;
		}
		// el� t�rl�s
		$db = JFactory::getDBO();
		$db->setQuery('delete from #__szavazatok 
		where user_id='.$db->quote($user->id).' and fordulo='.$db->quote($fordulo).' and szavazas_id = '.$db->quote($szavazas_id));
		$db->query();
		// ada hitelesit�si szint
		$ada0 = 0;
		$ada1 = 0;
		$ada2 = 0;
		$ada3 = 0;
		if (substr($user->params,0,1)=='[') $ada0 = 1;   // ADA
		if (strpos($user->params,'hash') > 0) $ada1 = 1; // ADA szem�lyes adatok alapj�n
		if (strpos($user->params,'email') > 0) $ada2 = 1; // ADA email aktiv�l�s
		if (strpos($user->params,'magyar') > 0) $ada3 = 1; // ADA szem�lyesen ellen�rz�tt
		// string r�szekre bont�sa �s t�rol�s ciklusban
		$w1 = explode(',',$szavazat);
		foreach ($w1 as $item) {
			$w2 = explode('=',$item);
			$db->setQuery('INSERT INTO #__szavazatok 
				(`temakor_id`, 
				`szavazas_id`, 
				`szavazo_id`, 
				`user_id`, 
				`alternativa_id`, 
				`pozicio`,
				`ada0`, `ada1`, `ada2`, `ada3`,
				`fordulo`
				)
				VALUES
				(8, 
				'.$db->quote($szavazas_id).', 
				'.$db->quote($user->id).', 
				'.$db->quote($user->id).', 
				'.$db->quote($w2[0]).', 
				'.$db->quote($w2[1]).',
				'.$ada0.','.$ada1.','.$ada2.','.$ada3.',
				'.$db->quote($fordulo).'
				)
			');
			if ($db->query() != true) {
			  $this->errorMsg .= $db->getErrorMsg().'<br />';
			  $result = false;
			}  
		}
		
		// delete cached report
		$db->setQuery('UPDATE #__eredmeny 
		SET report="" 
		WHERE pollid='.$db->quote($szavazas_id).' and fordulo='.$db->quote($fordulo) );
		$db->query();
		return $result;
	}	
	
	public function szavazatDelete($szavazas_id, $user, $fordulo) {
		$result = true;
		$db = JFactory::getDBO();
		$db->setQuery('delete 
		from #__szavazatok 
		where user_id='.$db->quote($user->id).' and fordulo='.$db->quote($fordulo).' and szavazas_id='.$db->quote($szavazas_id));
		$result = $db->query();
		$this->errorMsg = $db->getErrorMsg();
		// delete cached report
		$db->setQuery('UPDATE #__eredmeny 
		SET report="" 
		WHERE pollid='.$db->quote($szavazas_id).' and fordulo='.$db->quote($fordulo) );
		$db->query();
		return $result;  
	}
	
	/**
	* szavazas lista olvas�sa az adatb�zisb�l
	* @param integer temakor_id`
	* @return array of recordObject
	*/
	public function getSzavazasList($catid) {
		$db = JFactory::getDBO();
		$db->setQuery('select *
		from #__categories
		where parent_id = '.$catid.'
		order by title');
		$result = $db->loadObjectList();
		//DBG echo $db->getQuery();
		$this->setError($db->getErrorMsg());
		return $result;
	}
	
	/**
	* temakor rekord beolvas�sa az adatb�zisb�l
	* @param integer temakor_id`
	* @return recordObject
	*/
	public function getTemakor($catid) {
		$db = JFactory::getDBO();
		$db->setQuery('select *
		from #__categories
		where id = '.$catid.'
		order by title');
		$result = $db->loadObject();
		$this->setError($db->getErrorMsg());
		return $result;
		
	}
	
	/**
	* az adott user ebben a szavaz�sban jogosult szavazni?
	* @param szavazasObject szavazas
	* @param JUser user
	* @return boolean
	*/
	public function szavazasraJogousult($szavazas, $user) {
		if ($user->id <=0) return false;
		if (isset($szavazas->params) == false) return false;
		if (isset($szavazas->params->ASSURANCES) == false) return false;
		$userParams = JSON_encode($user->params);
		$userAssurances = explode(',',$userParams->ASSURANCE);
		$userAssurances[] = 'registered';
		$requAssurances = explode($szavazas->params->szavazok);
		$result = false;
		foreach ($requAssurances as $reqAssurance) {
			if (in_array($reqAssurance, $userAssurances)) $result = true;
		}
		return $result;
	}
	
	/**
	* az adott user ebben a szavaz�sban jogosult alternativ�t javasolni?
	* @param szavazasObject szavazas
	* @param JUser user
	* @return boolean
	*/
	public function altJavaslatraJogousult($szavazas, $user) {
		if ($user->id <=0) return false;
		if (isset($szavazas->params) == false) return false;
		if (isset($szavazas->params->ASSURANCES) == false) return false;
		$userParams = JSON_encode($user->params);
		$userAssurances = explode(',',$userParams->ASSURANCE);
		$userAssurances[] = 'registered';
		$requAssurances = explode($szavazas->params->altJavaslok);
		$result = false;
		foreach ($requAssurances as $reqAssurance) {
			if (in_array($reqAssurance, $userAssurances)) $result = true;
		}
		// superuser
		if (is_set($user->groups[8])) $result = true;
		// elovalasztokAdmin
		if (is_set($user->groups[10])) $result = true;
		return $result;
	}

	/**
	* az adott user ezt a szavaz�st vit�ra javasolhatja?
	* @param szavazasObject szavazas
	* @param JUser user
	* @return boolean
	*/
	public function vitaraJavaslatraJogousult($szavazas, $user) {
		if ($user->id <=0) return false;
		if (isset($szavazas->params) == false) return false;
		if (isset($szavazas->params->ASSURANCES) == false) return false;
		$userParams = JSON_encode($user->params);
		$userAssurances = explode(',',$userParams->ASSURANCE);
		$userAssurances[] = 'registered';
		$requAssurances = explode($szavazas->params->vitaraJavaslok);
		$result = false;
		foreach ($requAssurances as $reqAssurance) {
			if (in_array($reqAssurance, $userAssurances)) $result = true;
		}
		return $result;
	}
		
	/**
	* az adott user ebben a szavaz�sban m�r szavazott?
	* @param szavazasObject szavazas
	* @param JUser user
	* @return boolean
	*/
	public function marSzavazott($szavazas, $user) {
		
	}
	
	/**
	* az adott user ebben a szavaz�sban most szavazhat?
	* @param szavazasObject szavazas
	* @param JUser user
	* @return boolean - false eset�n errorMsg be�ll�tva
	*/
	public function mostSzavzahat($szavazas, $user) {
		
	}
	
	/**
	* az adott user ebben a szavaz�sban most �j lternativ�t javasolhat?
	* @param szavazasObject szavazas
	* @param JUser user
	* @return boolean  - false eset�n errorMsg be�ll�tva
	*/
	public function mostUjAlternativatJavasolhat($szavazas, $user) {
		
	}
	
	/**
	* az adott szavaz�st vit�ra javasolja
	* @param szavazasObj
	* @param JUser
	* @return boolean - hiba eset�n errorMsg be�ll�tva
	*/
	public function javasolja($szavazas, $user) {
		
	}
	
	/**
	* a vita megkezd�s�hez sz�ks�ges sz�m� javaslat 
	* @ param szavazasObj 
	* @ return integer
	*/
	public function getSzuksegesJavaslat($szavazas) {
		
	}
} 

?>