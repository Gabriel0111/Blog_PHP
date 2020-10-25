<?php

require_once('./echo.php');
require_once('./bibli.php');

// bufferisation des sorties
ob_start();

// démarrage de la session (pas utile pour le devoir 1 mais utile pour la suite)
session_start();

// vérification des éventuels paramètres transmis à la page
if (isset($_POST['btn']) && !fd_controle_parametres('post', array('t', 'btn'))) {
    fd_exit_session();
}

// affichage de l'entête
fd_entete('Toute l\'actu', '..', '../css/style.css');

fdl_contenu();

// pied de page
fd_pied();

// fin du script --> envoi de la page 
ob_end_flush();


/**
 *  Affichage du contenu principal de la page. 
 */
function fdl_contenu()
{

    echo '<main>';

    //-- Calcul des limites ------------------------------

    $totalArticles = $position = 0;
    $pagination = 4; // pas de de définition d'une constante dans echo.php car la pagination n'est utilisée que sur cette page

    // Au 1er passage il n'y a pas de soumission de
    // formulaire et le tableau $_POST est donc vide.

    if (isset($_POST['t']) && estEntier($_POST['t'])) {
        $totalArticles = (int) $_POST['t'];
    }

    if (isset($_POST['btn']) && estEntier($_POST['btn'])) {
        $position = (int) $_POST['btn'];
        $position = ($position - 1) * $pagination;
    }

    // Si paramètres POST "modifiés"
    if ($totalArticles < 0 || $position < 0 || $position >= $totalArticles) {
        $totalArticles = $position = 0;
    }

    $co = connecter();

    $sql = "SELECT * FROM article ORDER BY arDatePublication DESC";
    // Si pas 1er passage : ajoute clause LIMIT
    if ($totalArticles > 0) {
        $sql .= " LIMIT $position, $pagination";
    }

    $res = $co->query($sql) or bd_erreur($co, $sql);

    // Si 1er passage : calcul du nombre d'articles
    if ($totalArticles == 0) {
        $totalArticles = $res->num_rows;
    }

    // peut se produire si un autre utilisateur supprime un article pendant que l'utilisateur
    // courant navigue sur la page actus.php (rq : la suppression d'un article n'est pas implémentée)
    // ou s'il augmente le nombre total d'articles mémorisé dans l'input hidden
    if ($totalArticles > 0 && $res->num_rows == 0) {
        $res->free();
        $sql = "SELECT * FROM article ORDER BY arDatePublication DESC";
        $res = $co->query($sql) or bd_erreur($co, $sql);
        $totalArticles = $res->num_rows;
        $position = 0;
    }

    fdl_afficher_btnNavigation($position, $pagination, $totalArticles);

    $tRes = array();
    while ($t = $res->fetch_assoc()) {
        $tRes[] = $t;
        // filtrage pour n'afficher que les $pagination premiers articles lors du 1er passage
        if (count($tRes) == $pagination) {
            break;
        }
    }
    $res->free();

    $co->close();

    fd_afficher_resume($tRes);

    echo '</main>';
}


/**
 *  Affichage des boutons de navigation pour la pagination.
 *  
 */
function fdl_afficher_btnNavigation($position, $pagination, $totalArticles)
{

    //-- Affichage pagination ---------------------------
    echo '<form method="POST" ',
        'action="',
        $_SERVER['PHP_SELF'],
        '">',
        '<input type="hidden" name="t" ',
        'value="',
        $totalArticles,
        '">',
        '<p class="bandeau">Pages : ';

    for ($i = 0, $nb = 0; $i < $totalArticles; $i += $pagination) {
        $nb++;
        if ($i == $position) {  // page en cours, pas de lien
            echo "<span class='boutonLookAlike'>$nb</span>";
        } else {
            // Les boutons sont des boutons de type submit
            // qui ont tous le même nom. Ca n'a pas d'importance
            // car seul celui qui sera cliqué sera transmis avec 
            // le formulaire. Sa valeur permettra de définir à 
            // quel endroit on doit "limiter" le select.
            echo '<input type="submit" name="btn" ',
                'value="',
                $nb,
                '">';
        }
    }

    echo '</p></form>';
}
