<?php

require_once('./echo.php');
require_once('./bibli.php');

// bufferisation des sorties
ob_start();

// démarrage de la session
session_start();

// affichage de l'entête
fd_entete('Rédaction', '..', '../css/style.css');

fdl_contenu();

// pied de page
fd_pied();

// fin du script --> envoi de la page 
ob_end_flush();


/**
 *  Affichage du contenu de la page (purement statique).
 */
function fdl_contenu() {
    echo '<main id="redaction">',
        '<section>',
            '<h2>Le mot de la rédaction</h2>',
            '<p>Passionnés par le journalisme d\'investigation depuis notre plus jeune âge, nous avons créé en 2016 ce site pour répondre à un réel besoin : celui de fournir une information fiable et précise sur la vie de l\'EAD.</p>',
            '<p>Découvrez les hommes et les femmes qui composent l\'équipe de choc de l\'&Eacute;cho de l\'EAD. </p>',
        '</section>',
        '<section>',
            '<h2>Notre rédacteur en chef</h2>',
            '<article>',
                '<aside>',
                    '<img src="../images/johnny.jpg" width="150" height="200" alt="Johnny Bigoude">',
                '</aside>',
                '<h3 id="jbigoude">Johnny Bigoude</h3>',
                '<p>Récemment débarqué de la rédaction d\'iTélé suite au scandale Morandini, Johnny insuffle une vision nouvelle et moderne du journalisme au sein de notre rédaction. Leader charismatique et figure incontournable de l\'information en France et à l\'étranger, il est diplômé de la Harvard Business School of Bullshit, promotion 1997.</p>',
                '<p>Véritable puits de sagesse sans fond, Johnny est LA référence dans la rédaction. Présent dans les locaux du CTU, il suit au plus près l\'actualité de l\'EAD, et signe la majorité des articles du journal, en plus d\'en tracer la ligne éditoriale.</p>',
            '</article>',
        '</section>',
        '<section>',
            '<h2>Nos premiers violons</h2>',
            '<article>',
                '<aside>',
                    '<img src="../images/alex.jpg" width="150" height="200" alt="Alex Kuzbidon">',
                '</aside>',
                '<h3 id="akuz">Alex Kuzbidon</h3>',
                '<h4>Correspondant à l\'étranger</h4>',
                '<p>Sans cesse sur les théatres d\'opération aux 4 coins du monde, Alex prête régulièrement sa plume à l\'Echo de l\'EAD pour nous raconter les trépidentes aventures de nos étudiants en EAD à l\'étranger. </p>',
                '<p>Il a récemment suivi la trace d\'Emmanuel Macron en Russie et décroché une révélation tout à fait étonnante qui lui vaudra très certainement le prix Pullitzer l\'année prochaine. </p>',
            '</article>',
            '<article>',
                '<aside><img src="../images/kelly.jpg" width="150" height="200" alt="Kelly Diot"></aside>',
                '<h3 id="kdiot">Kelly Diot</h3>',
                '<h4>Journaliste d\'investigation</h4>',
                '<p>Ancienne détective privé, Kelly a rejoint l\'équipe l\'été dernier. Mettant à profit ses acquis d\'expérience de sa vie professionnelle antérieure, elle est tout particulièrement attachée aux enquêtes spéciales.</p>',
                '<p>Si ses articles sont rares, ce sont de petits bijoux d\'investigation qui sont régulièrement cités en exemple dans toutes les bonnes écoles de journalisme.</p>',
            '</article>',
        '</section>',
        '<section>',
            '<h2>Nos sous-fifres</h2>',
            '<article>',
                '<aside><img src="../images/pete.jpg" width="150" height="200" alt="Pete Heupakeur"></aside>',
                '<h3 id="pete">Pete Heupakeur</h3>',
                '<h4>Photographe officiel</h4>',
                '<p>Equipé de son reflex dernier cri, Pete est l\'oeil de l\'écho de l\'EAD. Ses clichés originaux viennent parfaitement illustrer les articles magistrement écrits par nos collaborateurs. </p>',
                '<p>Son meilleur cliché reste celui du Président Macron juste après avoir appris qu\'il validait sa Licence 
                d\'Informatique.</p>',
            '</article>',
            '<article>',
                '<aside><img src="../images/yves.jpg" width="150" height="200" alt="Yves Jourdelesse"></aside>',
                '<h3 id="yjourdelaisse">Yves Jourdelesse</h3>',
                '<h4>Typographe et webmaster</h4>',
                '<p>Responsable de l\'édition numérique du journal, Yves donne vie à nos articles dans un style CSS inimitable. Ancien étudiant d\'EAD Informatique (comme le laisse deviner son style vestimentaire et capillaire négligé), Yves travaille d\'arrache-pied pour offrir monde extérieur un contenu d\'un rendu impeccable. </p>',
                '<p>Puni suite à la fronde du calendrier de l\'EAD, Yves passe désormais la moitié de son temps de travail au 
                pilori, devant l\'entrée Ouest du CTU.</p>',
            '</article>',
        '</section>',
        '<section>',
            '<h2>L\'Echo de l\'EAD recrute !</h2>',
            '<p>Si vous souhaitez vous aussi faire partie de notre team, rien de plus simple. Envoyez-nous un mail grâce au lien dans le menu de navigation, et rejoignez l\'équipe. </p>',
        '</section>',
    '</main>';    
}


?>