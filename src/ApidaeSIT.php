<?php

namespace Apidae;

use PDO;

/**
 * Class ApidaeSIT
 *
 * @package ApidaeSIT
 */
class ApidaeSIT extends PDO
{
    public const tableUtilisateurs = ['table' => 'utilisateursitra', 'champs' => ['id', 'email', 'firstname', 'lastname']];
    public const tableMembres = ['table' => 'utilisateursitra', 'champs' => ['id', 'email', 'firstname', 'lastname']];
    public const cleTables = [
        'nom_id' => ['table' => 'traductionlibelle', 'champs' => ['libellefr']],
        'legende_id' => ['table' => 'traductionlibelle', 'champs' => ['libellefr']],
        'descriptifcourt_id' => ['table' => 'traductionlibelle', 'champs' => ['libellefr']],
        'descriptifdetaille_id' => ['table' => 'traductionlibelle', 'champs' => ['libellefr']],
        'tarifsenclair_id' => ['table' => 'traductionlibelle', 'champs' => ['libellefr']],
        'periodeenclair_id' => ['table' => 'traductionlibelle', 'champs' => ['libellefr']],
        'structuregestion_id' => ['table' => 'objettouristiquereference'],
        'membreauteurdemande_id' => self::tableMembres,
        'membreauteurtraitement_id' => self::tableMembres,
        'membrecreateur_id' => self::tableMembres,
        'membreproprietaire_id' => self::tableMembres,
        'membreauteurtraitement_id' => self::tableMembres,
        'utilisateurcreateur_id' => self::tableUtilisateurs,
        'utilisateurdernieremodification_id' => self::tableUtilisateurs,
        'utilisateurvalideur_id' => self::tableUtilisateurs,

        //'chaineetlabel_id' => ['table' => 'hebergementcollectifchaineetlabel', 'champs' => ['id', 'actif', 'description', 'parent_id', 'libellefr']],
    ];

    public const filestore = 'https://base.apidae-tourisme.com/filestore/objets-touristiques/images/';
    public const pathMultimedia = "case when tfm.hashed = true then (tfm.id::bit(8)::integer || '/' || (tfm.id::bit(16) & x'FF00' >> 8)::int || '/' ) else tfm.id::varchar end as path";

    public const tables_types_objet = [
        'ACTIVITE' => '',
        'COMMERCE_ET_SERVICE' => '',
        'DOMAINE_SKIABLE' => '',
        'EQUIPEMENT' => 'equipement',
        'STRUCTURE' => '',
        'FETE_ET_MANIFESTATION' => '',
        'HEBERGEMENT_COLLECTIF' => '',
        'PATRIMOINE_CULTUREL' => '',
        'PATRIMOINE_NATUREL' => '',
        'DEGUSTATION' => '',
        'RESTAURATION' => '',
        'SEJOUR_PACKAGE' => '',
        'TERRITOIRE' => '',
        'HOTELLERIE_PLEIN_AIR' => 'hotelleriepleinair',
        'HOTELLERIE' => 'hotellerie',
        'HEBERGEMENT_LOCATIF' => 'hebergementlocatif'
    ];
    public const TYPES_OBJET = self::tables_types_objet;
    public const TYPES_OBJET_COURTS = [
        'ACTIVITE' => 'ACT',
        'COMMERCE_ET_SERVICE' => 'COS',
        'DOMAINE_SKIABLE' => 'SKI',
        'EQUIPEMENT' => 'EQU',
        'STRUCTURE' => 'STR',
        'FETE_ET_MANIFESTATION' => 'EVE',
        'HEBERGEMENT_COLLECTIF' => 'HCO',
        'PATRIMOINE_CULTUREL' => 'PCU',
        'PATRIMOINE_NATUREL' => 'PNA',
        'DEGUSTATION' => 'PDT',
        'RESTAURATION' => 'RES',
        'SEJOUR_PACKAGE' => 'SEJ',
        'TERRITOIRE' => 'TER',
        'HOTELLERIE_PLEIN_AIR' => 'HPA',
        'HOTELLERIE' => 'HOT',
        'HEBERGEMENT_LOCATIF' => 'HLO'
    ];

    public const langs = ['fr', 'en', 'de', 'es', 'it', 'nl', 'ru', 'zh', 'ptbr', 'ja'];
    public const LANGS = self::langs;

    public const ASPECTS = ['STANDARD', 'HIVER', 'ETE', 'HANDICAP', 'GROUPES', 'TOURISME_AFFAIRES', 'PRESTATAIRE_ACTIVITES'];

    private $cache;

    /*
     public function offresMultipleValidated() {
     $sql = "select
     count(o.state), o.reference_id, o.membreproprietaire_id
     from
     sitra.objettouristique o
     where
     o.state = 'VALIDATED'
     group by
     o.state, o.reference_id
     having
     count(o.state) > 1
     order by
     count(o.state) desc
     " ;
     }
     */

    public function getHistoriqueActions($id_offre, $aspects = null, $champs = null)
    {
        $demandes = $this->get(['table' => 'demande', 'wheres' => ['reference_id' => $id_offre]]);
        $objettouristique = $this->get(['table' => 'objettouristique', 'wheres' => ['reference_id' => $id_offre]]);

        $prefixe = array('state', 'type');

        foreach ($demandes as $i => &$objet) {
            foreach ($objet as $cle => $valeur) {
                if ($valeur == null || $valeur == '') {
                    unset($objet[$cle]);
                }
                if (in_array($cle, $prefixe)) {
                    $objet['demande_' . $cle] = $valeur;
                    unset($objet[$cle]);
                }
            }
        }
        foreach ($objettouristique as $i => &$objet) {
            foreach ($objet as $cle => $valeur) {
                if ($valeur == null || $valeur == '') {
                    unset($objet[$cle]);
                }
                if ($cle == 'type') {
                    $objet['type_objet'] = $valeur;
                }
                if (in_array($cle, $prefixe)) {
                    $objet['objettouristique_' . $cle] = $valeur;
                    unset($objet[$cle]);
                }
            }
        }

        $results = array_merge($demandes, $objettouristique);
        usort($results, function ($a, $b) {
            return $a['datecreation'] < $b['datecreation'];
        });

        foreach ($results as $k => &$r) {
            foreach ($r as $k2 => &$v) {
                if (trim($v) == '') {
                    unset($r[$k2]);
                } else {
                    if (preg_match('#^[0-9]{4}-[0-9]{2}-[0-9]{2}#', $v)) {
                        $v = new \DateTime($v);
                    }
                }
            }
        }
        $columns = array_unique(array_merge(array_keys($demandes[0]), array_keys($objettouristique[0])));
        unset($columns['reference_id']);
        $columns = $this->sortColumns($columns);

        return [
            'columns' => $columns,
            'results' => $results
        ];
    }

    public function sortColumns($columns)
    {
        usort($columns, function ($a, $b) {
            $firsts = [
                'objettouristique_state', 'demande_state',
                'objettouristique_type', 'demande_type',
                'step', 'demandeworkflowtype', 'aspect', 'creationtype'
            ];

            if (in_array($a, $firsts)) {
                return false;
            }
            if (in_array($b, $firsts)) {
                return true;
            }

            if (preg_match('#date$#', $a) && !preg_match('#date$#', $b)) {
                return true;
            }
            if (!preg_match('#date$#', $a) && preg_match('#date$#', $b)) {
                return false;
            }
            if (preg_match('#date$#', $a) && preg_match('#date$#', $b)) {
                return strcmp($a, $b);
            }

            if (preg_match('#_id$#', $a) && !preg_match('#_id$#', $b)) {
                return true;
            }
            if (!preg_match('#_id$#', $a) && preg_match('#_id$#', $b)) {
                return false;
            }
            if (preg_match('#_id$#', $a) && preg_match('#_id$#', $b)) {
                return strcmp($a, $b);
            }

            if (preg_match('#_id$#', $a) && !preg_match('#_id$#', $b)) {
                return true;
            }
            if (!preg_match('#_id$#', $a) && preg_match('#_id$#', $b)) {
                return false;
            }
            if (preg_match('#_id$#', $a) && preg_match('#_id$#', $b)) {
                return strcmp($a, $b);
            }
        });

        return $columns;
    }

    public function getMembre($id)
    {
        return $this->get(['table' => 'membresitra', 'wheres' => ['id' => $id]]);
        //$sql = " select * from sitra.membresitra where id = :id ";
        //return $this->fetchAll($sql, ['id' => $id]);
    }

    public function fetchAll($sql, $params = null, $mode = PDO::FETCH_ASSOC)
    {
        $sth = $this->prepare($sql);
        if ($params != null) {
            $sth->execute($params);
        } else {
            $sth->execute();
        }
        return $sth->fetchAll($mode);
    }

    public function fetch($sql, $params = null, $mode = PDO::FETCH_ASSOC)
    {
        $sth = $this->prepare($sql);
        if ($params != null) {
            $sth->execute($params);
        } else {
            $sth->execute();
        }
        return $sth->fetch($mode);
    }

    /**
     * Renvoie le résultat d'une requête simple sur 1 table $table
     * avec des where $params
     * $params['minus'] permet de retirer des champs du résultats (plutôt que de lister des champs dans $params['champs'])
     */
    public function get(array $params)
    {
        $table = $params['table'];
        $wheres = isset($params['wheres']) ? $params['wheres'] : (isset($params['where']) ? $params['where'] : null);
        $champs = isset($params['champs']) ? $params['champs'] : ['*'];
        $order = isset($params['order']) ? $params['order'] : null;

        if (!preg_match('#^sitra#', $table)) {
            $table = 'sitra.' . $table;
        }

        $sql = 'select ' . implode(',', $champs) . ' from ' . $table;
        $params_sql = [];

        $wheres_sql = [];
        $i = 0;
        foreach ($wheres as $k => $v) {
            $i++;
            $wheres_sql[] = ' ' . $k . ' = :param' . $i . ' ';
            $params_sql['param' . $i] = $v;
        }

        $sql .= ' where ' . implode("and", $wheres_sql);

        if (isset($order) && $order != null) {
            $sql .= ' order by ' . $order;
        }


        $results = $this->fetchAll($sql, $params_sql);

        if (isset($params['minus']) && is_array($params['minus'])) {
            foreach ($results as $i => &$result) {
                foreach ($result as $k => $v) {
                    if (in_array($k, $params['minus'])) {
                        unset($result[$k]);
                    }
                }
            }
        }

        return $results;
    }

    public function getResultsFromKey(string $cle, string $id, array $helper = null)
    {
        $params = [
            'wheres' => ['id' => $id],
            'table' => null,
            'champs' => null
        ];

        if (isset(self::cleTables[$cle])) {
            $params['table'] = self::cleTables[$cle]['table'];
            if (isset(self::cleTables[$cle]['champs'])) {
                $params['champs'] = self::cleTables[$cle]['champs'];
            }
        } else {
            if (!preg_match('#^([a-z_]+)_id$#', $cle, $match)) {
                throw new \Exception('Incorrect cle parameter');
            }
            $cle_table = $match[1];

            $params['table'] = $cle_table;
            $params['wheres']['id'] = $id;

            // Si on a un type objet en helper, on regarde si on trouve une table TYPE_OBJET + table => typeobjettable
            if (!$this->tableExists($params['table']) && isset($helper['type_objet'])) {
                $table_to = mb_strtolower(preg_replace('#_#', '', $helper['type_objet']));
                if ($this->tableExists($table_to)) {
                    $params['table'] = $table_to;
                }
            }
        }

        if ($params['table'] == null) {
            throw new \Exception('No table found');
        }

        $results = $this->get($params);

        return $results;
    }

    public function tableExists(string $table)
    {
        $tmp = $this->fetchAll(
            " select exists ( select * from information_schema.tables where table_schema = 'sitra' and table_name = :table ) ",
            ['table' => $table]
        );
        $exists = @array_shift(@array_shift($tmp));
        return $exists;
    }

    /**
     * Transforme une date string YYYY-MM-DD en DateTime (pour twig)
     */
    public function setResultsDateTime(array $results)
    {
        foreach ($results as $k => &$r) {
            foreach ($r as $k2 => &$v) {
                if (preg_match('#^[0-9]{4}-[0-9]{2}-[0-9]{2}#', $v)) {
                    $v = new \DateTime($v);
                }
            }
        }
        return $results;
    }

    /**
     * Renvoie la liste des versions de l'objet $id_obt
     *
     */
    public function getVersions(int $id_obt, string $aspectname = 'STANDARD')
    {
        $wheres = ['reference_id' => $id_obt];
        if (isset($aspectname) && $aspectname != '') {
            $wheres['aspectname'] = $aspectname;
        }
        $versions = $this->get(['table' => 'objettouristique', 'wheres' => $wheres, 'order' => 'datecreation desc']);
        $versions = $this->setResultsDateTime($versions);
        $ret = [];
        foreach ($versions as $v) {
            $ret[$v['id']] = $v;
            //unset($ret[$v['id']]['id']);
        }
        return $ret;
    }

    /**
     * Renvoie un tableau rangé par version
     * @param   int $id_obt Identifiant de l'objet touristique de référence
     * @param   string  $type   Type de fichier recherché : multimedia|illustration
     * @param   string  $aspectname STANDARD|ETE|...
     */
    public function multimedias(int $id_obt, string $type = 'illustrations', string $aspectname = 'STANDARD')
    {
        $cle_principale = $type;
        $force = false;

        if (isset($cache[$cle_principale][$id_obt][$aspectname]) && !$force) {
            return $cache[$cle_principale][$id_obt][$aspectname];
        }

        $versions = $this->getVersions($id_obt, $aspectname);

        $cache_status = [];

        /**
         * On ne va chercher les status (200, 404...) que pour dernière version publiée (donc 1ère dans la boucle)
         */
        $first_version = true;
        foreach ($versions as $id_version => $iv) {
            $versions[$id_version][$cle_principale] = [];

            $join_table = $cle_principale == 'illustrations' ? 'objettouristique_illustration' : 'objettouristique_multimedia';
            $join_on = $cle_principale == 'illustrations' ? 'illustrations_id' : 'multimedias_id';

            $sql = '
                select 
                    m.id as id_multimedia, m.type, m.legende_id, m.nom_id, m.typestockage, m.etat, m.locked, m.link,
                    tfm.filename, tfm.extension, tfm.id as id_fichier, tfm.locale, tfm.url,
                ' . self::pathMultimedia . ',
                tfmb.id as brouillon_id, tfmb.extension as brouillon_extension
                from 
                sitra.objettouristique o
                inner join sitra.' . $join_table . ' oi on oi.objettouristique_id = o.id
                inner join sitra.multimedia m on m.id = oi.' . $join_on . ' 
                left outer join sitra.traductionfichiermultimedia tfm on tfm.multimedia_id = m.id 
                left outer join sitra.traductionfichiermultimediabrouillon tfmb on tfmb.id = tfm.brouillon_id
                where o.id = :id_version and o.aspectname = :aspectname
            ';

            $fichiers = $this->fetchAll($sql, ['id_version' => $iv['id'], 'aspectname' => $aspectname]);

            foreach ($fichiers as $f) {
                if (!isset($iv[$cle_principale][$f['id_multimedia']])) {
                    $iv[$cle_principale][$f['id_multimedia']] = $f;
                    unset($iv[$cle_principale][$f['id_multimedia']]['filename']);
                    unset($iv[$cle_principale][$f['id_multimedia']]['extension']);
                    unset($iv[$cle_principale][$f['id_multimedia']]['id_fichier']);
                    unset($iv[$cle_principale][$f['id_multimedia']]['locale']);
                    unset($iv[$cle_principale][$f['id_multimedia']]['path']);
                    $iv[$cle_principale][$f['id_multimedia']]['fichiers'] = [];
                }

                $folder = $cle_principale == 'illustrations' ? 'images' : 'documents';
                $path = 'https://static.apidae-tourisme.com/filestore/objets-touristiques/' . $folder . '/' . $f['path'];
                if ($f['brouillon_id'] != null) {
                    $path = 'https://static.apidae-tourisme.com/filestore/objets-touristiques-brouillons/';
                }

                $fichier = [];
                if ($f['link'] == true) {
                    $fichier['url'] = $f['url'];
                } else {
                    if ($f['brouillon_id'] != null) {
                        $fichier['url'] = $path . $f['brouillon_id'] . '.' . $f['brouillon_extension'];
                    } else {
                        $fichier['url'] = $path . $f['id_fichier'] . '.' . $f['extension'];
                    }
                }

                if ($cle_principale == 'illustrations') {
                    $fichier['urlListe'] = $path . $f['id_fichier'] . '-liste.' . $f['extension'];
                    $fichier['urlFiche'] = $path . $f['id_fichier'] . '-fiche.' . $f['extension'];
                    $fichier['urlDiaporama'] = $path . $f['id_fichier'] . '-diaporama.' . $f['extension'];
                }

                $versions[$id_version][$cle_principale][$f['id_multimedia']]['fichiers'][$f['locale']] = $fichier;

                $i = 0;
                if ($first_version && ($_SERVER['APP_ENV'] == 'prod' || $i++ < 5)) {
                    $status = null;

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $fichier['url']);
                    //curl_setopt($ch, CURLOPT_CONNECT_ONLY, 1);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
                    curl_setopt($ch, CURLOPT_NOBODY, 1);
                    curl_setopt($ch, CURLOPT_HEADER, 1);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    $return = curl_exec($ch);
                    $status = null;
                    if (curl_errno($ch)) {
                        $status = curl_error($ch);
                    } else {
                        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    }
                    curl_close($ch);
                    $versions[$id_version][$cle_principale][$f['id_multimedia']]['status'] = $status;
                }
            }
            $first_version = false;
        }

        $cache[$cle_principale][$id_obt][$aspectname] = $versions;
        return $versions;
    }

    public function multimediaStatus(int $id_obt, string $type = 'illustrations', string $aspectname = 'STANDARD')
    {
        $cle_principale = $type;
        $status = [];
        $illustrations = $this->multimedias($id_obt, $type, $aspectname);
        foreach ($illustrations as $id_version => $version) {
            if (isset($version[$cle_principale])) {
                foreach ($version[$cle_principale] as $illus) {
                    if (isset($illus['status'])) {
                        if (!isset($status[$illus['status']])) {
                            $status[$illus['status']] = 0;
                        }
                        $status[$illus['status']]++;
                    }
                }
            }
            break; // 1 seul tour : les status ne sont calculés que pour la dernière version en cours
        }
        return $status;
    }

    /**
     *
     */
    public function getHistoriqueChamps(int $id_offre, $aspectname = 'STANDARD')
    {
        $versions = [];

        $tables_joins = [
            'identification' => [
                'nom' => ['type' => 'traductionlibelle'],
                'structuregestion' => [
                    'type' => 'objettouristiquereference',
                    'champs' => ['id']
                ],
                'structureinformation' => [
                    'type' => 'objettouristiquereference',
                    'champs' => ['id']
                ],
                'adresse' => [
                    'champs' => ['adresse1', 'adresse2', 'adresse3', 'codepostal'],
                ],
                'commune' => [
                    'champs' => ['nom'],
                    'join_on' => 'commune.id = adresse.commune_id'
                ]
            ],
            'presentation' => [
                'descriptifcourt' => ['type' => 'traductionlibelle'],
                'descriptifdetaille' => ['type' => 'traductionlibelle'],

            ],
            'localisation' => [
                'geolocalisation' => [
                    'champs' => ['latitude', 'longitude', 'valide']
                ]
            ]
        ];

        $selects = ['o.id', 'o.state', 'o.datemodification'];
        $joins = [];

        foreach ($tables_joins as $categorie => $table_join) {
            foreach ($table_join as $table => $details) {
                if (isset($details['type']) && $details['type'] == 'traductionlibelle') {
                    $joins[$table] = 'left outer join sitra.traductionlibelle as ' . $table . ' on ' . $table . '.id = o.' . $table . '_id';
                    $build = [];
                    foreach (self::langs as $lang) {
                        $selects[$table . $lang] = $table . '.libelle' . $lang . ' as ' . $categorie . '__' . $table . '__trad__' . $lang;
                    }
                } else {
                    $cle = $table . '_id';
                    $table_alias = $table;
                    if (isset($details['type']) && $details['type'] == 'objettouristiquereference') {
                        $table = 'objettouristiquereference';
                    }

                    foreach ($details['champs'] as $c) {
                        $selects[$table_alias . '.' . $c] = $table_alias . '.' . $c . ' as ' . $categorie . '__' . $table_alias . '__' . $c;
                    }

                    $join = ' left outer join sitra.' . $table . ' ' . $table_alias . ' on ';
                    if (isset($details['join_on'])) {
                        $join .= $details['join_on'];
                    } else {
                        $join .= $table_alias . '.id = o.' . $cle;
                    }

                    $joins[$table_alias] = $join;
                }
            }
        }

        $sql = ' select ' . implode("\n" . ', ', $selects) . ' from sitra.objettouristique o ';
        $sql .= "\n" . implode("\n", $joins);
        $sql .= "\n" . ' where o.reference_id = :id_offre and o.aspectname = :aspectname ';

        $obt = $this->fetchAll($sql, ['id_offre' => $id_offre, 'aspectname' => $aspectname]);
        $versions = [];
        foreach ($obt as $v) {
            $versions[$v['id']] = $v;
        }

        foreach ($versions as &$v) {
            $v['datemodification'] = new \DateTime($v['datemodification']);
            foreach ($v as $cle_champ => $valeur_champ) {
                if (preg_match('#^([a-z]+)__([a-z]+)__([a-z0-9]+)$#', $cle_champ, $reg)) {
                    $v[$reg[1]][$reg[2]][$reg[3]] = $valeur_champ;
                    unset($v[$cle_champ]);
                } elseif (preg_match('#^([a-z]+)__([a-z]+)__trad__([a-z0-9]+)$#', $cle_champ, $reg)) {
                    $v[$reg[1]][$reg[2]][$reg[3]] = $valeur_champ;
                    unset($v[$cle_champ]);
                }
            }
            $v['ouverture'] = [];
            $v['illustrations'] = [];
        }

        // Traitement à part pour les moyens de com'
        $coords = ['coordonnee as fr'];
        foreach (self::langs as $lang) {
            $coords[$lang] = 'coordonnee' . $lang . ' as ' . $lang;
        }
        unset($coords['fr']);
        $sql = 'select o.id as id_version, m.identifiant, mctype.libellefr as mctype, ' . implode(', ', $coords) . '
            from sitra.objettouristique_moyencommunication om 
            inner join sitra.moyencommunication m on m.id = om.moyenscommunication_id 
            inner join sitra.objettouristique o on o.id = om.objettouristique_id 
            inner join sitra.moyencommunicationtype mctype on mctype.id = m.type_id 
            where o.reference_id = :id_offre and aspectname = :aspectname
            order by om.position asc';
        $mcs = $this->fetchAll($sql, ['id_offre' => $id_offre, 'aspectname' => $aspectname]);
        foreach ($mcs as $mc) {
            $coords = [];
            foreach (self::langs as $lang) {
                $coords[$lang] = $mc[$lang];
            }
            $versions[$mc['id_version']]['moyenscom'][$mc['identifiant']] = $coords;
        }

        $versions_to = $this->getChampsTypeObjet($id_offre);
        if (is_array($versions_to)) {
            foreach ($versions_to as $id_version => $infos_to) {
                foreach ($infos_to as $table_name => $valeurs) {
                    $versions[$id_version][$table_name] = $valeurs;
                }
            }
        }

        return $versions;
    }

    public function getChampsTypeObjet($id_offre, $type = null, $aspectname = 'STANDARD')
    {
        if ($type == null) {
            $sql = ' select o.type from sitra.objettouristique o inner join sitra.objettouristiquereference r on r.id = o.reference_id 
            where r.id = :id_offre and o.aspectname = :aspectname order by o.datemodification desc limit 1 ';
            $type = $this->fetch($sql, ['id_offre' => $id_offre, 'aspectname' => $aspectname]);
            if ($type) {
                $type = $type['type'];
            }
        }
        if (!$type || !isset(self::tables_types_objet[$type])) {
            return false;
        }

        $table_type = self::tables_types_objet[$type];
        if ($table_type == '') {
            return false;
        }

        $selects = ['t.*'];
        $joins = [
            'inner join sitra.objettouristique o on o.id = t.id',
            'inner join sitra.objettouristiquereference r on r.id = o.reference_id'
        ];

        /**
         * Cas des tables 1...N
         * Ex: hotelleriepleinair.hotelleriepleinairtype <> hotelleriepleinairtype
         */
        if ($table_type == 'hotelleriepleinair') {
            $selects[] = 'y.libellefr as ' . $table_type . 'type';
            $joins[] = ' left outer join sitra.' . $table_type . 'type y on y.id = t.' . $table_type . 'type_id ';
            $selects[] = 'c.libellefr as classement';
            $joins[] = ' left outer join sitra.' . $table_type . 'classement c on c.id = t.classement_id ';
        } elseif ($table_type == 'hotellerie') {
            $selects[] = 'y.libellefr as ' . $table_type . 'type';
            $joins[] = ' left outer join sitra.' . $table_type . 'type y on y.id = t.' . $table_type . 'type_id ';
            $selects[] = 'c.libellefr as classement';
            $joins[] = ' left outer join sitra.' . $table_type . 'classement c on c.id = t.classement_id ';
        } elseif ($table_type == 'hebergementlocatif') {
            $selects[] = 'y.libellefr as ' . $table_type . 'type';
            $joins[] = ' left outer join sitra.' . $table_type . 'type y on y.id = t.' . $table_type . 'type_id ';
            $selects[] = 'c.libellefr as typelabel';
            $joins[] = ' left outer join sitra.' . $table_type . 'typelabel c on c.id = t.typelabel_id ';
            $selects[] = 'cp.libellefr as classementprefectoral';
            $joins[] = ' left outer join sitra.' . $table_type . 'classementprefectoral cp on cp.id = t.classementprefectoral_id ';
        } elseif ($table_type == 'equipement') {
            $selects[] = 'y.libellefr as ' . $table_type . 'type';
            $joins[] = ' left outer join sitra.' . $table_type . 'rubrique y on y.id = t.rubrique_id ';
        }

        $sql = ' select ' . implode(', ', $selects) . ' from sitra.' . $table_type . ' t
        ' . implode("\n\t", $joins) . '
        where r.id = :id_offre and o.aspectname = :aspectname
        order by o.id desc ';
        $infos = $this->fetchAll($sql, ['id_offre' => $id_offre, 'aspectname' => $aspectname]);

        $versions = [];
        foreach ($infos as $i) {
            $versions[$i['id']][$table_type] = $i;
        }

        /**
         * Cas des tables N...N
         * Ex: hotelleriepleinair <> hotelleriepleinair_hotelleriepleinairchaine <> hotelleriepleinairchaine
         */
        $tablesNN = [
            'hotelleriepleinair' => [
                'hotelleriepleinairchaine' => [
                    'champs' => ['t.libellefr as hotelleriepleinairchaine'],
                    'tablenn' => 'hotelleriepleinair_hotelleriepleinairchaine',
                    'joinsnn' => ['chaines_id', 'hotelleriepleinair_id']
                ],
                'hotelleriepleinairlabel' => [
                    'champs' => ['t.libellefr as hotelleriepleinairlabel'],
                    'tablenn' => 'hotelleriepleinair_hotelleriepleinairlabel',
                    'joinsnn' => ['labels_id', 'hotelleriepleinair_id']
                ]
            ],
            'hotellerie' => [
                'hotelleriechaine' => [
                    'champs' => ['t.libellefr as hotelleriechaine'],
                    'tablenn' => 'hotellerie_hotelleriechaine',
                    'joinsnn' => ['chaines_id', 'hotellerie_id']
                ],
                'hotellerielabel' => [
                    'champs' => ['t.libellefr as hotellerieplabel'],
                    'tablenn' => 'hotellerie_hotellerielabel',
                    'joinsnn' => ['labels_id', 'hotellerie_id']
                ],
                'typehabitation' => [
                    'champs' => ['t.libellefr as typehabitation'],
                    'tablenn' => 'hotellerie_typehabitation',
                    'joinsnn' => ['typeshabitation_id', 'hotellerie_id']
                ]
            ],
            'hebergementlocatif' => [
                'hebergementlocatifagrement' => [
                    'champs' => ['t.numero as agrement_numero', 't.type_id as agrement_type_id'],
                    'tablenn' => 'hebergementlocatif_hebergementlocatifagrement',
                    'joinsnn' => ['agrements_id', 'hebergementlocatif_id']
                ],
                'hebergementlocatiflabel' => [
                    'champs' => ['t.libellefr as hebergementlocatiflabel'],
                    'tablenn' => 'hebergementlocatif_hebergementlocatiflabel',
                    'joinsnn' => ['labels_id', 'hebergementlocatif_id']
                ],
                'typehabitation' => [
                    'champs' => ['t.libellefr as typehabitation'],
                    'tablenn' => 'hebergementlocatif_typehabitation',
                    'joinsnn' => ['typeshabitation_id', 'hebergementlocatif_id']
                ]
            ],
            'equipement' => [
                'natureterrain' => [
                    'champs' => ['t.libellefr as natureterrain'],
                    'tablenn' => 'equipement_natureterrain',
                    'joinsnn' => ['naturesterrain_id', 'equipement_id']
                ],
                'equipementactivite' => [
                    'champs' => ['t.libellefr as equipementactivite'],
                    'tablenn' => 'equipement_equipementactivite',
                    'joinsnn' => ['activites_id', 'equipement_id']
                ]
            ]
        ];
        foreach ($tablesNN[$table_type] as $table => $details) {
            $champs = $details['champs'];
            $tablenn = $details['tablenn'];
            $joinsnn = $details['joinsnn'];
            $sql = ' select o.id as id_version, ' . implode(', ', $champs) . ' from sitra.' . $table . ' t
                inner join sitra.' . $tablenn . ' tablenn on tablenn.' . $joinsnn[0] . ' = t.id
                inner join sitra.objettouristique o on o.id = tablenn.' . $joinsnn[1] . '
                inner join sitra.objettouristiquereference r on r.id = o.reference_id
                where r.id = :id_offre and o.aspectname = :aspectname ';
            $valeurs = $this->fetchAll($sql, ['id_offre' => $id_offre, 'aspectname' => $aspectname]);
            $to_implode = [];
            foreach ($valeurs as $v) {
                $id_version = $v['id_version'];
                unset($v['id_version']);
                //$versions[$id_version][$table_type][$table] = $v;
                $versions[$id_version][$table_type][$table][] = implode('|', $v);
            }
        }

        return $versions;
    }




    /**
     * https://apidae-tourisme.zendesk.com/agent/tickets/17686
     *  - recherches enregistrées qui utilisent $id_offre comme filtre de territoire
     *  - listes enregistrées qui possèdent $id_offre
     *  - ?
     */
    public function usage($id_offre)
    {
        $usage = [];

        $sql = " select rechercheavanceeenregistree from sitra.listeobjetstouristiquesenregistree where 
            rechercheavanceeenregistree  like '%\"territoireId\" \: " . $id_offre . "'
        ";
        $usage['requete'] = $this->fetchAll($sql, []);

        $sql = ' select l.* from sitra.objettouristiquereference r
        inner join sitra.selection_objettouristiquereference s on s.objetstouristiquesreferences_id = r.id
        inner join sitra.listeobjetstouristiquesenregistree l on l.id = s.listeobjetstouristiquesenregistree_id
        where r.id = :id_offre ';
        $usage['listes'] = $this->fetchAll($sql, ['id_offre' => $id_offre]);

        $sql = ' select l.* from sitra.objettouristiquereference r
        inner join sitra.selection_objettouristiquereference s on s.objetstouristiquesreferences_id = r.id
        inner join sitra.listeobjetstouristiquesenregistree l on l.id = s.listeobjetstouristiquesenregistree_id
        where r.id = :id_offre ';
        $usage['listes'] = $this->fetchAll($sql, ['id_offre' => $id_offre]);

        return $usage;
    }

    public function getDescriptifsThematises()
    {
        $sql = ' select d.id, d.libellefr, string_agg(eo.objettouristiquetypesinterdits,\'|\') as interdits from sitra.descriptiftheme d 
        left outer join sitra.elementreference_objettouristiquetypesinterdits eo on eo.elementreference_id = d.id 
        group by d.id, d.libellefr ';
        $results = $this->fetchAll($sql);
        $return = [];
        foreach ($results as $r) {
            $interdits = explode('|', $r['interdits']);
            $interdits[] = 'STRUCTURE';
            $autorises = array_diff(array_keys(self::TYPES_OBJET), $interdits);

            $return[$r['id']] = [
                'libellefr' => $r['libellefr'],
                'types_objets_autorises' => $autorises,
                'types_objets_interdits' => $interdits,
            ];
        }
        return $return;
    }

    public function getTypesObjetCourts($entree = null)
    {
        if (is_array($entree)) {
            $sortie = [];
            foreach ($entree as $e) {
                $sortie[$e] = self::TYPES_OBJET_COURTS[$e];
            }
            return $sortie;
        } elseif (is_string($entree)) {
            return self::TYPES_OBJET_COURTS[$entree];
        }
        return self::TYPES_OBJET_COURTS;
    }
}
