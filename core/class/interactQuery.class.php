<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../core/php/core.inc.php';

class interactQuery {
    /*     * *************************Attributs****************************** */

    private $id;
    private $interactDef_id;
    private $query;
    private $link_type;
    private $link_id;
    private $enable = 1;

    /*     * ***********************Methode static*************************** */

    public static function byId($_id) {
        $values = array(
            'id' => $_id
        );
        $sql = 'SELECT ' . DB::buildField(__CLASS__) . '
                FROM interactQuery
                WHERE id=:id';

        return DB::Prepare($sql, $values, DB::FETCH_TYPE_ROW, PDO::FETCH_CLASS, __CLASS__);
    }

    public static function byQuery($_query) {
        $values = array(
            'query' => $_query
        );
        $sql = 'SELECT ' . DB::buildField(__CLASS__) . '
                FROM interactQuery
                WHERE query=:query';
        return DB::Prepare($sql, $values, DB::FETCH_TYPE_ROW, PDO::FETCH_CLASS, __CLASS__);
    }

    public static function byInteractDefId($_interactDef_id, $_enable = false) {
        $values = array(
            'interactDef_id' => $_interactDef_id
        );
        $sql = 'SELECT ' . DB::buildField(__CLASS__) . '
                FROM interactQuery
                WHERE interactDef_id=:interactDef_id';
        if ($_enable) {
            $sql .= ' AND enable=1';
        }
        return DB::Prepare($sql, $values, DB::FETCH_TYPE_ALL, PDO::FETCH_CLASS, __CLASS__);
    }

    public static function all() {
        $sql = 'SELECT ' . DB::buildField(__CLASS__) . '
                FROM interactQuery
                ORDER BY id';
        return DB::Prepare($sql, array(), DB::FETCH_TYPE_ALL, PDO::FETCH_CLASS, __CLASS__);
    }

    public static function removeByInteractDefId($_interactDef_id) {
        $values = array(
            'interactDef_id' => $_interactDef_id
        );
        $sql = 'DELETE FROM interactQuery
                WHERE interactDef_id=:interactDef_id';
        return DB::Prepare($sql, $values, DB::FETCH_TYPE_ALL, PDO::FETCH_CLASS, __CLASS__);
    }

    public static function recognize($_query) {
        $values = array(
            'query' => $_query,
        );
        $sql = 'SELECT id, MATCH query AGAINST (:query IN NATURAL LANGUAGE MODE) as score 
                FROM interactQuery 
                GROUP BY id
                HAVING score > 1
                ORDER BY score DESC,enable DESC';
        $results = DB::Prepare($sql, $values, DB::FETCH_TYPE_ALL);
        $queries = array();
        $highest = 0;
        foreach ($results as $result) {
            if ($result['score'] >= $highest) {
                $highest = $result['score'];
                $queries[] = self::byId($result['id']);
            }
        }
        $shortest = 999;
        foreach ($queries as $query) {
            $input = $query->getQuery();
            preg_match_all("/#(.*?)#/", $input, $matches);
            foreach ($matches[1] as $match) {
                $input = str_replace('#' . $match . '#', '', $input);
            }
            $lev = levenshtein(strtolower($_query), strtolower($query->getQuery()));
            if ($lev == 0) {
                $closest = $query;
                $shortest = 0;
                break;
            }
            if ($lev <= $shortest || $shortest < 0) {
                $closest = $query;
                $shortest = $lev;
            }
        }
        if (!isset($query)) {
            return null;
        }
        return $query;
    }

    public static function whatDoYouKnow($_object = null) {
        $results = jeedom::whatDoYouKnow($_object);
        $reply = '';
        foreach ($results as $object) {
            $reply .= 'Je sais que pour ' . $object['name'] . " : \n";
            foreach ($object['eqLogic'] as $eqLogic) {
                foreach ($eqLogic['cmd'] as $cmd) {
                    $reply.= $eqLogic['name'] . ' ' . $cmd['name'] . ' vaut ' . $cmd['value'] . ' ' . $cmd['unite'] . "\n";
                }
            }
        }
        return $reply;
    }

    public static function tryToReply($_query, $_parameters = array()) {
        $_parameters['dictation'] = $_query;
        if (isset($_parameters['profile'])) {
            $_parameters['profile'] = ucfirst($_parameters['profile']);
        }
        $reply = self::dontUnderstand($_parameters);

        $interactQuery = self::byQuery($_query);
        if (!is_object($interactQuery)) {
            $interactQuery = interactQuery::recognize($_query);
        }
        if (is_object($interactQuery)) {
            $reply = $interactQuery->executeAndReply($_parameters);
        }

        if (!is_object($interactQuery)) {
            $brainReply = self::brainReply($_query, $_parameters);
            if ($brainReply != '') {
                $reply = $brainReply;
            }
        }
        return ucfirst($reply);
    }

    public static function brainReply($_query, $_parameters) {
        global $PROFILE;
        $PROFILE = '';
        if (isset($_parameters['profile'])) {
            $PROFILE = $_parameters['profile'];
        }
        include_file('core', 'bot', 'config');
        global $BRAINREPLY;
        $shortest = 999;
        foreach ($BRAINREPLY as $word => $response) {
            $lev = levenshtein(strtolower($_query), strtolower($word));
            if ($lev == 0) {
                $closest = $word;
                $shortest = 0;
                break;
            }
            if ($lev <= $shortest || $shortest < 0) {
                $closest = $word;
                $shortest = $lev;
            }
        }
        if (isset($closest) && $BRAINREPLY[$closest]) {
            $random = rand(0, count($BRAINREPLY[$closest]) - 1);
            return $BRAINREPLY[$closest][$random];
        }
        return '';
    }

    public static function dontUnderstand($_parameters) {
        $notUnderstood = array(
            'Désolé je n\'ai pas compris',
            'Désolé je n\'ai pas compris la demande',
            'Désolé je ne comprends pas la demande',
            'Je ne comprends pas',
        );
        if (isset($_parameters['profile'])) {
            $notUnderstood[] = 'Désolé ' . $_parameters['profile'] . ' je n\'ai pas compris';
            $notUnderstood[] = 'Désolé ' . $_parameters['profile'] . ' je n\'ai pas compris ta demande';
        }
        $random = rand(0, count($notUnderstood) - 1);
        return $notUnderstood[$random];
    }

    /*     * *********************Methode d'instance************************* */

    public function save() {
        if ($this->getQuery() == '') {
            throw new Exception('La commande vocale ne peut etre vide');
        }
        if ($this->getInteractDef_id() == '') {
            throw new Exception('SarahDef_id ne peut etre vide');
        }
        if ($this->getLink_id() == '' && $this->getLink_type() != 'whatDoYouKnow') {
            throw new Exception('Cette ordre vocale n\'est associé à aucune commande : ' . $this->getQuery());
        }
        $checksum = DB::checksum('interactQuery');
        DB::save($this);
        if ($checksum != DB::checksum('interactQuery')) {
            $internalEvent = new internalEvent();
            $internalEvent->setEvent('update::interactQuery');
            $internalEvent->save();
        }
        return true;
    }

    public function remove() {
        $internalEvent = new internalEvent();
        $internalEvent->setEvent('update::interactQuery');
        $internalEvent->save();
        return DB::remove($this);
    }

    public function executeAndReply($_parameters) {
        $interactDef = interactDef::byId($this->getInteractDef_id());
        if (!is_object($interactDef)) {
            return 'Inconsistance de la base de données';
        }
        if (isset($_parameters['profile']) && $interactDef->getPerson() != '') {
            $person = $interactDef->getPerson();
            $person = explode('|', $person);
            if (!in_array($_parameters['profile'], $person)) {
                return 'Tu n\es pas autorisé à executer cette action';
            }
        }
        if ($this->getLink_type() == 'whatDoYouKnow') {
            $object = object::byId($this->getLink_id());
            if (is_object($object)) {
                return self::whatDoYouKnow($object);
            }
            return self::whatDoYouKnow();
        }

        $reply = $interactDef->selectReply();
        $replace = array();
        $replace['#heure#'] = date('H\hi');
        $replace['#date#'] = date('l F Y');
        $replace['#jour#'] = date('l');
        $replace['#datetime#'] = date('l F Y H\hi');

        if ($this->getLink_type() == 'cmd') {
            $cmd = cmd::byId($this->getLink_id());
            if (!is_object($cmd)) {
                log::add('interact', 'error', 'Commande : ' . $this->getLink_id() . ' introuvable veuillez renvoyer les listes des commandes');
                return 'Commande introuvable verifier qu\'elle existe toujours';
            }

            $replace['#objet#'] = '';
            $replace['#equipement#'] = '';
            $eqLogic = $cmd->getEqLogic();
            if (is_object($eqLogic)) {
                $replace['#equipement#'] = $eqLogic->getName();
                $object = $eqLogic->getObject();
                if (is_object($object)) {
                    $replace['#objet#'] = $object->getName();
                }
            }

            $replace['#unite#'] = $cmd->getUnite();
            if ($cmd->getType() == 'action') {
                $options = null;
                $query = $this->getQuery();
                preg_match_all("/#(.*?)#/", $query, $matches);
                $matches = $matches[1];
                if (count($matches) > 0) {
                    if (!isset($_parameters['dictation'])) {
                        return 'Erreur aucune phrase envoyé. Impossible de remplir les trous';
                    }
                    $dictation = $_parameters['dictation'];
                    $options = array();
                    $start = 0;
                    $bitWords = array();
                    foreach ($matches as $match) {
                        $bitWords[] = substr($query, $start, strpos($query, '#' . $match . '#') - $start);
                        $start = strpos($query, '#' . $match . '#') + strlen('#' . $match . '#');
                    }
                    if ($start < strlen($query)) {
                        $bitWords[] = substr($query, $start);
                    }
                    $i = 0;
                    foreach ($matches as $match) {
                        if (isset($bitWords[$i])) {
                            $start = strpos($dictation, $bitWords[$i]);
                        } else {
                            $start = 0;
                        }
                        if (isset($bitWords[$i + 1])) {
                            $end = strpos($dictation, $bitWords[$i + 1]);
                            $options[$match] = trim(substr($dictation, $start + strlen($bitWords[$i]), $end - ($start + strlen($bitWords[$i]))));
                        } else {
                            $options[$match] = trim(substr($dictation, $start + strlen($bitWords[$i])));
                        }

                        $i++;
                    }
                }
                try {
                    if ($cmd->execCmd($options) === false) {
                        return 'Impossible d\'executer la commande';
                    }
                } catch (Exception $exc) {
                    return $exc->getMessage();
                }
                if ($options != null) {
                    foreach ($options as $key => $value) {
                        $replace['#' . $key . '#'] = $value;
                    }
                }
            }
            if ($cmd->getType() == 'info') {
                $value = $cmd->execCmd();
                if ($value === null) {
                    return 'Impossible de recuperer la valeur de la commande';
                } else {
                    $replace['#valeur#'] = $value;
                    if ($cmd->getSubType() == 'binary' && $interactDef->getOptions('convertBinary') != '') {
                        $convertBinary = $interactDef->getOptions('convertBinary');
                        $convertBinary = explode('|', $convertBinary);
                        $replace['#valeur#'] = $convertBinary[$replace['#valeur#']];
                    }
                }
            }
        }
        $reply = str_replace(array_keys($replace), $replace, $reply);
        return $reply;
    }

    /*     * **********************Getteur Setteur*************************** */

    public function getInteractDef_id() {
        return $this->interactDef_id;
    }

    public function setInteractDef_id($interactDef_id) {
        $this->interactDef_id = $interactDef_id;
    }

    public function getId() {
        return $this->id;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function getQuery() {
        return $this->query;
    }

    public function setQuery($query) {
        $this->query = $query;
    }

    public function getLink_type() {
        return $this->link_type;
    }

    public function setLink_type($link_type) {
        $this->link_type = $link_type;
    }

    public function getLink_id() {
        return $this->link_id;
    }

    public function setLink_id($link_id) {
        $this->link_id = $link_id;
    }

    public function getEnable() {
        return $this->enable;
    }

    public function setEnable($enable) {
        $this->enable = $enable;
    }

}

?>
