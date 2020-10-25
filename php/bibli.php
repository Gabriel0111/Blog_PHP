<?php

//___________________________________________________________________
/**
 * Connexion à une base de données MySQL.
 * En cas d'erreur de connexion le script est arrêté.
 *
 * @return objet connecteur à la base de données
 */
function connecter()
{
	$bd = mysqli_connect(BD_SERVER, BD_USER, BD_PASS, BD_NAME);

	if ($bd !== FALSE) {
		//mysqli_set_charset() définit le jeu de caractères par défaut à utiliser
		//lors de l'envoi de données depuis et vers le serveur de base de données.
		mysqli_set_charset($bd, 'utf8') or
			bd_erreurExit('<h4>Erreur lors du chargement du charset utf8</h4>');
		return $bd;		// Sortie connexion OK
	}

	// Erreur de connexion
	// Collecte des informations facilitant le debugage
	$msg = '<h4>Erreur de connexion base MySQL</h4>'
		. '<div style="margin: 20px auto; width: 350px;">'
		. 'BD_SERVER : ' . BD_SERVER
		. '<br>BD_USER : ' . BD_USER
		. '<br>BD_PASS : ' . BD_PASS
		. '<br>BD_NAME : ' . BD_NAME
		. '<p>Erreur MySQL num&eacute;ro : ' . mysqli_connect_errno($bd)
		. '<br>' . htmlentities(mysqli_connect_error(), ENT_QUOTES, 'ISO-8859-1')
		//appel de htmlentities() pour que les éventuels accents s'affiche correctement
		. '</div>';

	bd_erreurExit($msg);
}


//___________________________________________________________________
/**
 * Arrêt du script si erreur base de données.
 * Affichage d'un message d'erreur si on est en phase de
 * développement, sinon stockage dans un fichier log.
 *
 * @param string    $msg    Message affichÃ© ou stockÃ©.
 */
function bd_erreurExit($msg)
{
	ob_end_clean();        // Supression de tout ce qui
	// a pu être déja généré

	echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>',
		'Erreur base de données</title></head><body>',
		$msg,
		'</body></html>';
	exit();
}


//___________________________________________________________________
/**
 * Gestion d'une erreur de requête à la base de données.
 *
 * @param objet	$bd		Connecteur sur la bd ouverte
 * @param string	$sql	requête SQL provoquant l'erreur
 */
function bd_erreur($bd, $sql)
{
	$errNum = mysqli_errno($bd);
	$errTxt = mysqli_error($bd);

	// Collecte des informations facilitant le debugage
	$msg =  '<h4>Erreur de requête</h4>'
		. "<b>Erreur mysql :</b> $errNum"
		. "<br> $errTxt"
		. "<br><br><b>Requête :</b><br><pre>$sql</pre>"
		. '<br><br><b>Pile des appels de fonction :</b>';

	$tdStyle = 'style="border: 1px solid black;padding: 4px 10px"';

	// Récupération de la pile des appels de fonction
	$msg .= '<table style="border-collapse: collapse">'
		. "<tr><td $tdStyle>Fonction</td>"
		. "<td $tdStyle>Appelée ligne</td>"
		. "<td $tdStyle>Fichier</td></tr>";

	$appels = debug_backtrace();
	for ($i = 0, $iMax = count($appels); $i < $iMax; $i++) {
		$msg .= "<tr style='text-align: center'><td $tdStyle>"
			. $appels[$i]['function'] . "</td><td $tdStyle>"
			. $appels[$i]['line'] . "</td><td $tdStyle>"
			. $appels[$i]['file'] . '</td></tr>';
	}

	$msg .= '</table>';

	bd_erreurExit($msg);
}


/** 
 *  Protection des sorties (code HTML généré à destination du client).
 *
 *  Fonction à appeler pour toutes les chaines provenant de :
 *      - de saisies de l'utilisateur (formulaires)
 *      - de la bdD
 *  Permet de se protéger contre les attaques XSS (Cross site scripting)
 *  Convertit tous les caractères éligibles en entités HTML, notamment :
 *      - les caractères ayant une signification spéciales en HTML (<, >, ...)
 *      - les caractères accentués
 * 
 *  Si on lui transmet un tableau, la fonction renvoie un tableau où toutes les chaines
 *  qu'il contient sont protégées, les autres données du tableau ne sont pas modifiées. 
 *
 *  @param  mixed  $content   la chaine à protéger ou un tableau contenant des chaines à protéger 
 *  @return mixed  			  la chaîne protégée ou le tableau
 */
function proteger_sortie($content)
{
	if (is_array($content)) {
		foreach ($content as &$value) {
			$value = proteger_sortie($value);
		}
		unset($value); // à ne pas oublier (de façon générale)
		return $content;
	}
	if (is_string($content)) {
		return htmlentities($content, ENT_QUOTES, 'UTF-8');
	}
	return $content;
}


/**
 *  Protection des entrées (chaînes envoyées au serveur MySQL)
 * 
 * Avant insertion dans une requête SQL, certains caractères spéciaux doivent être échappés (", ', ...).
 * Toutes les chaines de caractères provenant de saisies de l'utilisateur doivent être protégées 
 * en utilisant la fonction mysqli_real_escape_string() (si elle est disponible)
 * Cette dernière fonction :
 * - protège les caractères spéciaux d'une chaîne (en particulier les guillemets)
 * - permet de se protéger contre les attaques de type injections SQL. 
 *
 *  Si on lui transmet un tableau, la fonction renvoie un tableau où toutes les chaines
 *  qu'il contient sont protégées, les autres données du tableau ne sont pas modifiées.  
 *	
 *	@param 	objet	$co			l'objet représantant la connexion au serveur MySQL
 *	@param 	mixed	$content	la chaine à protéger ou un tableau contenant des chaines à protéger 
 *  @return mixed  				la chaîne protégée ou le tableau
 */
function proteger_entree($co, $content)
{
	if (is_array($content)) {
		foreach ($content as &$value) {
			$value = proteger_entree($co, $value);
		}
		unset($value); // à ne pas oublier (de façon générale)
		return $content;
	}
	if (is_string($content)) {
		if (function_exists('mysqli_real_escape_string')) {
			return mysqli_real_escape_string($co, $content);
		}
		if (function_exists('mysqli_escape_string')) {
			return mysqli_escape_string($co, $content);
		}
		return addslashes($content);
	}
	return $content;
}

//___________________________________________________________________
/**
 * Teste si un nombre est compris entre 2 autres
 *
 * @param integer	$x	nombre ‡ tester
 * @return boolean	TRUE si ok, FALSE sinon
 */
function estEntre($x, $min, $max)
{
	return ($x >= $min) && ($x <= $max);
}

//___________________________________________________________________
/**
 * Teste si une valeur est une valeur entiËre
 *
 * @param mixed		$x	valeur ‡ tester
 * @return boolean	TRUE si entier, FALSE sinon
 */
function estEntier($x)
{
	return is_numeric($x) && ($x == (int) $x);
}

define('CLE_CRYPTAGE', 'c1kOFj9LS0Z0azZJ0bTE3w==');
define('CLE_HACHAGE', '/OCtJxoYdlg69+8MxVxen7HyZwboBvwvUEWF7Ywczj4=');

//___________________________________________________________________
/**
 * Crypte une valeur pour la passer dans une URL.
 *
 * @param mixed		$val	La valeur à crypter
 * @return string	La valeur cryptée encodée url
 */
function crypterURL($val)
{
	// -- longueur du vecteur d'initialisation
	$ivlen = openssl_cipher_iv_length($cipher = 'AES-128-CBC');
	// -- génération du vecteur d'initialisation
	$iv = openssl_random_pseudo_bytes($ivlen);
	// -- cryptage de $val
	$x = openssl_encrypt($val, $cipher, base64_decode(CLE_CRYPTAGE), OPENSSL_RAW_DATA, $iv);
	// -- calcul de la signature de la valeur cryptée
	$hmac = hash_hmac('sha256', $x, base64_decode(CLE_HACHAGE), true);
	$sha2len = 32;
	$x = substr($hmac, 0, $sha2len / 2)
		. $iv . $x . substr($hmac, $sha2len / 2);
	$x = base64_encode($x);
	return urlencode($x);
}
//___________________________________________________________________
/**
 * Décrypte une valeur cryptée avec la fonction crypterURL
 *
 * @param string	$x	La valeur à décrypter
 * @return mixed	La valeur décryptée ou FALSE si erreur
 */
function decrypterURL($x)
{
	$ivlen = openssl_cipher_iv_length($cipher = 'AES-128-CBC');
	$x = base64_decode($x);
	$sha2len = 32;
	$hmac = substr($x, 0, $sha2len / 2) . substr($x, -$sha2len / 2);
	$iv = substr($x, $sha2len / 2, $ivlen);
	$x = substr($x, $sha2len / 2 + $ivlen, -$sha2len / 2);
	// calcul de  la signature de la chaine cryptée reçue
	$hmacCalc = hash_hmac('sha256', $x, base64_decode(CLE_HACHAGE), true);
	if (!hash_equals($hmac, $hmacCalc)) {
		return FALSE;
	}
	return openssl_decrypt($x, $cipher, base64_decode(CLE_CRYPTAGE), OPENSSL_RAW_DATA, $iv);
}
//___________________________________________________________________
/**
 * Créé une liste déroulante à partir des options passées en paramètres.
 *
 * @param string	$nom   	   Le nom de la liste déroulante
 * @param array	    $options   Un tableau associatif donnant la liste des options sous la forme valeur => libelle 
 * @param string    $default   La valeur qui doit être sélectionnée par défaut. 
 */
function fd_creer_select($nom, $options, $defaut)
{
	echo '<select name="', $nom, '">';
	foreach ($options as $valeur => $libelle) {
		echo '<option value="', $valeur, '"', (($defaut == $valeur) ? ' selected' : ''), '>', $libelle, '</option>';
	}
	echo '</select>';
}
//___________________________________________________________________
/**
 * Créé une liste déroulante d'une suite de nombre à partir des options passées en paramètres.
 *
 * @param string	$nom   	   Le nom de la liste déroulante
 * @param int	    $min       La valeur minimale de la liste
 * @param int       $max       La valeur maximale de la liste 
 * @param int       $pas       La pas d'itération (si positif, énumération croissante, sinon décroissante) 
 * @param int       $default   La valeur qui doit être sélectionnée par défaut. 
 */
function fd_creer_liste_nombre($nom, $min, $max, $pas, $defaut)
{
	echo '<select name="', $nom, '">';
	if ($pas > 0) {
		for ($i = $min; $i <= $max; $i += $pas) {
			echo '<option value="', $i, '"', (($defaut == $i) ? ' selected' : ''), '>', $i, '</option>';
		}
	} else {
		for ($i = $max; $i >= $min; $i += $pas) {
			echo '<option value="', $i, '"', (($defaut == $i) ? ' selected' : ''), '>', $i, '</option>';
		}
	}
	echo '</select>';
}
//___________________________________________________________________
/**
 * Renvoie un tableau contenant le nom des mois (utile pour certains affichages)
 *
 * @return array	Tableau à indices numériques contenant les noms des mois
 */
function get_tableau_mois()
{
	return array('Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre');
}

function fd_creer_liste_mois($nom, $defaut)
{
	$mois = get_tableau_mois();
	$m = array();
	foreach ($mois as $k => $v) {
		$m[$k + 1] = mb_strtolower($v, 'UTF-8');
		// comme on est en UTF-8 on utilise la fonction mb_strtolower
		// voir : https://www.php.net/manual/fr/function.mb-strtolower.php
	}
	fd_creer_select($nom, $m, $defaut);
}

//___________________________________________________________________
/**
 * Contrôle des clés présentes dans les tableaux $_GET ou $_POST - piratage ?
 *
 * Si une clé obligatoire est absente, la session de l'utilisateur est supprimée et il
 * est redirigé vers la page index.php (via l'appel de fd_exit_session())
 * Si une clé non autorisée (ie. n'appartenant pas à l'union de l'ensemble des clés facultatives
 * et de l'ensemble des clés obligatoires) est trouvée, idem.
 *
 * @param string	$tab_global	'post' ou 'get'
 * @param array		$cles_obligatoires tableau contenant les clés qui doivent obligatoirement être présentes
 * @param array		$cles_facultatives tableau contenant les clés facultatives (correspondant aux checkboxs)
 * @return boolean  true si les paramètres sont corrects, false sinon
 */
function fd_controle_parametres($tab_global, $cles_obligatoires, $cles_facultatives = array())
{
	if ($tab_global == 'post') {
		$x = $_POST;
	} else if ($tab_global == 'get') {
		$x = $_GET;
	} else {
		return;
	}
	$x = array_keys($x);
	// $cles_obligatoires doit être inclus dans $x
	if (count(array_diff($cles_obligatoires, $x)) > 0) return false;
	// $x doit être inclus dans $cles_obligatoires Union $cles_facultatives
	if (count(array_diff($x, array_merge($cles_obligatoires, $cles_facultatives))) > 0) return false;

	return true;
}

//___________________________________________________________________
/**
 * Vérifie si le mot de passe entré correspond à celui présent dans 
 * la base de données.
 * 
 * @param mysqli	$co 	instance de connexion (pour ne pas se connecter
 * 							une deuxième fois)
 * @param string	$pseudo	pseudo sur lequel nous comparons le passe
 * @param string 	$entree	passe entré dans la page, que nous allons
 * 							comparer à celui présent dans la BDD.
 * @return boolean	true : 	le mot de passe correspond
 * 					false : le cas inverse
 */
function verifier_passe($pseudo, $password)
{
	$co = connecter();

	$pseudo = proteger_entree($co, $pseudo);
	$password = proteger_entree($co, $password);

	$sql = "SELECT * FROM `utilisateur`
			WHERE utPseudo = '" . $pseudo . "';";

	$res = $co->query($sql) or bd_erreur($co, $sql);
	$enr = mysqli_fetch_object($res);

	$co->close();

	if (isset($enr->utPasse) && password_verify($password, $enr->utPasse)) {
		$result = array($enr->utRedacteur, $enr->utPseudo);
		mysqli_free_result($res);
		return $result;
	} else
		return NULL;
}

/**
 * Affiche une erreur.
 * 
 * @param string    $erreur    Message d'erreur à afficher
 */
function affiche_erreur($message, $erreur)
{
	echo "<div class='erreur'>$message $erreur</div>";
}

/**
 * Affiche une message de succès.
 * 
 * @param string    $erreur    Message de réussite à afficher
 */
function affiche_OK($message)
{
	echo "<div class='succes'>$message</div>";
}
