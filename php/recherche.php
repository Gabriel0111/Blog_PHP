<?php

require_once('./echo.php');
require_once('./bibli.php');

// bufferisation des sorties
ob_start();

// démarrage de la session (pas utile pour le devoir 1 mais utile pour la suite)
session_start();

// vérification des éventuels paramètres transmis à la page
if (isset($_POST['btnRechercher']) && !fd_controle_parametres('post', array('requete', 'btnRechercher' ))) {
    fd_exit_session();
}

// affichage de l'entête
fd_entete('Recherche', '..', '../css/style.css');

fdl_contenu();

// pied de page
fd_pied();

// fin du script --> envoi de la page 
ob_end_flush();



/**
 * Affichage du contenu principal de la page.
 */
function fdl_contenu() {
    
    $rech = isset($_POST['requete']) ? trim($_POST['requete']) : '';
    $tRech = explode(' ', $rech);
    
	// le tableau parcouru est modifié dans la boucle foreach
	// On accède aux valeurs par référence pour économiser de la mémoire, c'est à dire 
	// pour éviter qu'une copie du tableau parcouru soit faite
    foreach ($tRech as $cle => &$r) {
        if (mb_strlen($r, 'UTF-8') < 3) {
            unset($tRech[$cle]);
        }
    }
	unset($r); // à ne pas oublier

    echo '<main>',
        '<section>',
            '<h2>Rechercher des articles</h2>',
            '<p>Les critères de recherche doivent faire au moins 3 caractères pour être pris en compte.</p>',
            '<form action="recherche.php" method="post" style="text-align: center; margin: 10px;">',
                '<input type="text" name="requete" value="', proteger_sortie(implode(' ', $tRech)), '" style="width: 50%;">', 
                '<input type="submit" value="Rechercher" name="btnRechercher">', 
            '</form>',
        '</section>';
    
    if (count($tRech) > 0) {
        
        $co = connecter();
		
		$tRech = proteger_entree($co, $tRech);
		
		$sql = 'SELECT * FROM article WHERE 1=1';
		foreach ($tRech as $r) {
			$sql .= " AND (arTitre LIKE '%{$r}%' OR arResume LIKE '%{$r}%')";
		}
		$sql .= ' ORDER BY arDatePublication DESC';
		
        $res = $co->query($sql) or bd_erreur($co, $sql);
    
        if ($res->num_rows == 0) {
            echo '<section><p>Aucun article ne correspond à vos critères de recherche.</p></section>';
        }
        else {
            $tRes = array();
            while ($t = $res->fetch_assoc()) {
                $tRes[] = $t; 
            }
            fd_afficher_resume($tRes);
        }
        $res->free();
        $co->close();
    }
	else if (isset($_POST['requete'])){
		echo '<section><p>Le critère de recherche "', proteger_sortie(trim($_POST['requete'])), '" n\'est pas valide.</p></section>';
	}
    
    echo '</main>';
    
}



?>