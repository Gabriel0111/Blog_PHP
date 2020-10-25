<?php

require_once('./php/echo.php');
require_once('./php/bibli.php');

// bufferisation des sorties
ob_start();

// démarrage de la session
session_start();

fd_entete('Accueil', '.', './css/style.css');

fdl_contenu();

fd_pied();

ob_end_flush();


function fdl_contenu() {
     
    $co = connecter();
    
    echo '<main id="index">';
    
    // génération des 3 derniers articles
    $sql0 = 'SELECT * FROM article 
			 INNER JOIN utilisateur ON arAuteur = utPseudo 
			 ORDER BY arDatePublication DESC 
			 LIMIT 0, 3';
    $tab0 = fd_calcule_articles_pour_requete($co, $sql0);
    fd_afficher_vignettes('Les dernières parutions', $tab0, '.');
    
    // génération des 3 articles les plus commentés
    $sql1 = 'SELECT article.* 
			 FROM (article INNER JOIN utilisateur ON arAuteur = utPseudo) 
			 LEFT OUTER JOIN commentaire ON coArticle = arID 
			 GROUP BY arID 
			 ORDER BY COUNT(coArticle) DESC, rand() 
			 LIMIT 0, 3';
    $tab1 = fd_calcule_articles_pour_requete($co, $sql1);
    fd_afficher_vignettes('Les articles les plus commentés', $tab1, '.');
    
    // génération des 3 articles parmi les articles restants 
    $sql2 = 'SELECT * FROM article 
	         INNER JOIN utilisateur ON arAuteur = utPseudo 
			 WHERE arID NOT IN (' . join(',',array_keys($tab0)) . ',' . join(',',array_keys($tab1)) . ') 
			 ORDER BY rand() LIMIT 0, 3';
    $tab2 = fd_calcule_articles_pour_requete($co, $sql2);
    fd_afficher_vignettes('Les incontournables', $tab2, '.');
    
    // affichage de l'horoscope 
    fdl_horoscope();
    
    $co->close();
    
    echo '</main>';
    
}



/** 
 *  Fonction générant l'horoscope (texte purement statique)
 */ 
function fdl_horoscope() {
    echo
        '<section>',
            '<H2>Horoscope du semestre</H2>',
            '<p>Vous l\'attendiez tous, voici l\'horoscope du semestre impair de l\'année 2019-2020.',
            'Sans surprise, il n\'est pas terrible...</p>',
            '<table id="horoscope">',
                '<tbody>',
                    '<tr>',
                        '<td>Signe</td>',
                        '<td>Date</td>',
                        '<td>Votre horoscope</td>',
                    '</tr>',
                    '<tr>',
                        '<td>&#9800; Bélier</td>',
                        '<td>du 21 mars<br>au 19 avril</td>',
                        '<td rowspan="4">',
                            '<p>Après des vacances bien méritées, l\'année reprend sur les chapeaux de roues. ',
                                'Tous les signes sont concernés. </p>',
                            '<p>Jupiter s\'aligne avec Saturne, péremptoirement à Venus, et nous promet un semestre ', 
                                'qui ne sera pas de tout repos. Octobre sera le mois le plus tranquille puisque les ',
                                'cours ne commencent qu\'à partir de la deuxième quinzaine.</p>',
                            '<p>Les fins de mois seront douloureuses pour les natifs du 2e décan qui vont enchaîner ',
                                'les galères avec la gestion des sessions en PHP, en particulier durant les fêtes de fin d\'année.</p>',
                        '</td>',
                    '</tr>',
                    '<tr>',
                        '<td>&#9801; Taureau</td>',
                        '<td>du 20 avril<br>au 20 mai</td>',
                    '</tr>',
                    '<tr>',
                        '<td>...</td>',
                        '<td>...</td>',
                    '</tr>',
                    '<tr>',
                        '<td>&#9811; Poisson</td>',
                        '<td>du 20 février<br>au 20 mars</td>',
                    '</tr>',
                '</tbody>',
            '</table>',
            '<p>Malgré cela, notre équipe d\'astrologues de choc vous souhaite à tous une bonne rentrée, et bon courage pour ce semestre.</p>',
        '</section>';
   
}


?>