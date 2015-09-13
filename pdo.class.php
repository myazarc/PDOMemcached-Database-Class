<?php

/*
 * PDO Database Class
 * 	
 * @package		MYAZARC Classes
 * @category            Database Class
 * @author		myazarc
 * @require		Memcached (optional)
 * @createtime	15:40 08.04.2014 (H:i d.m.Y)[Europa/Istanbul]
 * @updatetime	18:30 13.09.2015 (H:i d.m.Y)[Europa/Istanbul]
 * @version		v1.2
 * @license     http://myazarc.com/myazarc-classes-license/
 * @see			http://myazarc.com/pdo-memcached-class/
 */

class db {
    #global db connect config

    var $db_type = 'mysql';  // database type: only use mysql,mssql,firebird,oracle,sqlite
    var $db_host = 'localhost';   // database host or database location
    var $db_user = 'root';  // database user
    var $db_pass = '12345';  // database password
    var $db_name = 'test';  // database name or database file location
    var $db_port = '3306';  // database port
    var $db_serna = 'orcl';    // service name (only use oci(oracle)) 
    var $db_cache = FALSE;  // database for cache. only use TRUE,FALSE
    # memcache config
    var $link = '127.0.0.1';          // memcached url
    var $port = 11211;                // memcached port
    var $cachetime = 300;                   // second 
    var $cachezlip = FALSE;                // cachezlip only use bool (recommended FALSE)
    var $key_prefix = 'myazarc';            // key prefix (optional)
    private $cache;                             // returning memcache connection
    private $db_conn = NULL;                    // returning database connection
    var $is_cache = false;
    private $lastsql = '';
    private $where = 'where ';
    private $countwhere = 0;
    private $select = '';
    private $selectcount = 0;
    private $from = '';
    private $fromcount = 0;
    private $orderby;
    private $limit;
    private $join = '';
    private $groub = '';
    private $groubcount = 0;
    private $having = '';
    private $havingcount = 0;
    private $orderbycount = 0;
    private $rowcount = 0;
    private $lastinsertid;
    private $queryStatus = 1;
    private $delimiter = false;
    private $delimiterCount = 0;
    private $result;
    private $prepare = array();
    private $lastprepare = array();
    private $getFetchColumn = false;
    private $getFetchColumnNumber = 0;

    function __construct() {
        $this->connectdb();
        if ($this->db_cache === TRUE) {
            $this->connectmem();
        }
    }

    public function connectmem() {
        $this->cache = new Memcache();
        $this->cache->pconnect($this->link, $this->port);
    }

    private function mem_set($key, $cache) {
        $key = $this->key_prefix . md5($key);
        $this->cache->set($key, json_encode($cache), $this->cachezlip, $this->cachetime);
    }

    private function mem_get($key) {
        $key = $this->key_prefix . md5($key);
        return json_decode($this->cache->get($key));
    }

    public function connectdb() {

        if ($this->db_type == 'mysql') {
            $this->db_conn_mysql();
            $this->db_conn->exec('SET NAMES "utf8"');
        } elseif ($this->db_type == 'mssql') {
            $this->db_conn_mssql();
        } elseif ($this->db_type == 'firebird') {
            $this->db_conn_firebird();
        } elseif ($this->db_type == 'oracle') {
            $this->db_conn_oci();
        } elseif ($this->db_type == 'sqlite') {
            $this->db_conn_sqlite();
        } //if $this->db_type end
    }

//function connect end;

    private function db_conn_mysql() {
        try {
            $this->db_conn = new PDO('mysql:host=' . $this->db_host . ';dbname=' . $this->db_name . '; port=' . $this->db_port, $this->db_user, $this->db_pass, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
        } catch (Exception $exc) {
            $this->show_err('Connection Error', $exc->getMessage());
            exit();
        }
    }

// function db_conn_mysql end;
    private function db_conn_sqlite() {
        try {
            $this->db_conn = new PDO('sqlite:' . $this->db_host);
            $this->db_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (Exception $exc) {
            $this->show_err('Connection Error', $exc->getMessage());
            exit();
        }
    }

// function db_conn_sqllite end;

    private function db_conn_mssql() {
        try {
            $this->db_conn = new PDO('dblib:host=' . $this->db_host . ';dbname=' . $this->db_name . '; port=' . $this->db_port, $this->db_user, $this->db_pass, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
        } catch (Exception $exc) {
            $this->show_err('Connection Error', $exc->getMessage());
            exit();
        }
    }

// function db_conn_mssql end;

    private function db_conn_firebird() {
        try {
            $this->db_conn = new PDO('firebird:dbhost:' . $this->db_host . ';dbname=' . $this->db_name, $this->db_user, $this->db_pass, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
        } catch (Exception $exc) {
            $this->show_err('Connection Error', $exc->getMessage());
            exit();
        }
    }

// function db_conn_firebird end;

    private function db_conn_oci() {
        $tns = " 
                    (DESCRIPTION =
                            (ADDRESS_LIST =
                              (ADDRESS = (PROTOCOL = TCP)(HOST = " . $this->db_host . ")(PORT = " . $this->db_port . "))
                            )
                            (CONNECT_DATA =
                              (SERVICE_NAME = " . $this->db_serna . ")
                            )
                      )
               ";
        try {
            $this->db_conn = new PDO("oci:dbname=" . $tns, $this->db_user, $this->db_pass, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
        } catch (Exception $exc) {
            $this->show_err('Connection Error', $exc->getMessage());
            exit();
        }
    }

// function db_conn_oci end;

    public function showtables() {
        $tableList = array();
        $result = $this->db_conn->query("SHOW TABLES");
        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $tableList[] = $row[0];
        }

        $this->lastsql = 'SHOW TABLES';
        $this->rowcount = $result->rowCount();
        return $tableList;
    }

    public function showtablefields($tablename, $issql = FALSE) {
        $column = array();
        if ($issql) {
            $select = $this->db_conn->query($tablename);
        } else {
            $select = $this->db_conn->query("SELECT * FROM $tablename LIMIT 1");
        }
        $total_column = $select->columnCount();

        for ($counter = 0; $counter < $total_column; $counter ++) {
            $meta = $select->getColumnMeta($counter);
            $column[] = (object) $meta;
        }
        $this->lastsql = 'SELECT * FROM $tablename LIMIT 1';
        $this->rowcount = $total_column;
        return $column;
    }

    public function getCol($number = 0) {
        $this->getFetchColumn = true;
        $this->getFetchColumnNumber = $number;
        return $this;
    }

    public function query($sql) {
        $result = "";
        $this->lastsql = $sql;
        if ($this->db_cache === TRUE) {

            if ($this->getFetchColumn) {
                $cSql = $sql . $this->getFetchColumnNumber;
            } else {
                $cSql = $sql;
            }

            if (!$this->mem_get($cSql)) {
                try {
                    $qctrl = $this->db_conn->prepare($sql);
                    $query = $qctrl->execute($this->prepare);
                    $this->queryStatus = boolval($query);
                    if ($this->queryStatus) {
                        if ($this->getFetchColumn) {
                            $result = $qctrl->fetchColumn($this->getFetchColumnNumber);

                            $this->mem_set($sql . $this->getFetchColumnNumber, $res);
                            $this->rowcount = 1;
                        } else {

                            if ($res = $qctrl->fetchAll()) {
                                foreach ($res as $w) {
                                    $result[] = (object) $w;
                                } // foreach query end;
                                $this->mem_set($sql, $result);
                                $this->rowcount = count($result);
                            }
                        }
                    } else {
                        $this->show_err($sql, $this->db_conn->errorInfo());
                    } // if query end;
                } catch (PDOException $e) {
                    $this->show_err($sql, $e->getMessage());
                }//try catch end;
                $this->lastprepare = $this->prepare;
                $this->prepare = array();
                return $result;
            } else {
                $this->is_cache = TRUE;

                if ($this->getFetchColumn) {
                    $result = $this->mem_get($sql . $this->getFetchColumnNumber);
                } else {
                    $result = $this->mem_get($sql);
                }

                $this->rowcount = count($result);
                $this->prepare = array();
                return $result;
            }
        } else {

            try {
                $qctrl = $this->db_conn->prepare($sql);
                $query = $qctrl->execute($this->prepare);
                $this->queryStatus = boolval($query);
                if ($this->queryStatus) {

                    if ($this->getFetchColumn) {
                        $result = $qctrl->fetchColumn($this->getFetchColumnNumber);

                        $this->rowcount = 1;
                    } else {

                        if ($res = $qctrl->fetchAll()) {
                            foreach ($res as $w) {
                                $result[] = (object) $w;
                            } // foreach query end;
                            $this->rowcount = count($result);
                        }
                    }
                } else {
                    $this->show_err($sql, $this->db_conn->errorInfo());
                } // if query end;
            } catch (PDOException $e) {
                $this->show_err($sql, $e->getMessage());
            }//try catch end;
            $this->lastprepare = $this->prepare;
            $this->is_cache = FALSE;
            $this->result = $result;
            $this->prepare = array();
            return $result;
        }
    }

    public function exec($sql) {
        $query = $this->db_conn->exec($sql);
        $this->queryStatus = boolval($query);
        $this->lastsql = $sql;
        return $this;
    }

// function query end;

    public function extract() {
        if ($this->rowcount == 1) {
            return $this->result[0];
        } else {
            return $this->result;
        }
    }

    public function sum($field, $asfield) {
        if ($this->selectcount > 0) {
            $this->select.=',' . "SUM($field) as $asfield";
        } else {
            $this->select.="SUM($field) as $asfield";
        }

        $this->selectcount++;
        return $this;
    }

    public function avg($field, $asfield) {
        if ($this->selectcount > 0) {
            $this->select.=',' . "AVG($field) as $asfield";
        } else {
            $this->select.="AVG($field) as $asfield";
        }

        $this->selectcount++;
        return $this;
    }

    public function count($field, $asfield) {
        if ($this->selectcount > 0) {
            $this->select.=',' . "COUNT($field) as $asfield";
        } else {
            $this->select.="COUNT($field) as $asfield";
        }

        $this->selectcount++;
        return $this;
    }

    public function distinct($field, $asfield) {
        if ($this->selectcount > 0) {
            $this->select.=',' . "DISTINCT($field) as $asfield";
        } else {
            $this->select.="DISTINCT($field) as $asfield";
        }

        $this->selectcount++;
        return $this;
    }

    public function masterdetailInsert($mastertable, $masterdata, $detailtable, $detaildata, $masterField, $detailField, $clausefield = 'ID') {

        $sql = '';
        $hata = true;
        if (is_array($masterdata) && is_array($detaildata)) {
            $this->beginTransaction();
            $this->insert($mastertable, $masterdata);
            $sql+=$this->lastsql . "; \n";
            $id = $this->lastinsertid();
            $lastID = $this->lastinsertid();
            if (!($masterField == 'ID' || $masterField == 'id')) {
                $id_query = $this->select($masterField)->from($mastertable)->where($clausefield, $id)->limit(1)->get();
                $id = $id_query[0]->$masterField;
            }

            foreach ($detaildata as $q => $k) {
                if (!is_array($k)) {
                    $detaildata[$detailField] = $id;

                    $this->insert($detailtable, $detaildata);
                    if (!$this->queryStatus) {
                        $this->rollback();
                        $hata = false;
                        break;
                    }
                    $sql+=$this->lastsql . "; \n";

                    break;
                } else {
                    $m[$detailField] = $id;
                    foreach ($k as $d) {
                        $m[$q] = $d;
                        $this->insert($detailtable, $m);
                        if (!$this->queryStatus) {
                            $this->rollback();
                            $hata = false;
                            break;
                        }
                        $sql+=$this->lastsql . "; \n";
                    }
                }
            }
            if ($hata) {
                $this->commit();
            }
            $this->lastsql = $sql;
        }
        return $lastID;
    }

    public function masterdetailDelete($mastertable, $detailtable, $masterField, $detailField, $where = 1) {


        if (!$this->countwhere) {
            $where = $masterField . '=\'' . $where . '\'';
            $id = $this->select($masterField)->where_static($where)->get($mastertable);
        } else {
            $id = $this->select($masterField)->get($mastertable);
        }

        $this->delete($mastertable, $where);

        if (count($id) > 1) {
            $in = array();
            foreach ($id as $q) {
                $in[] = $q->id;
            }

            $this->where_in($detailField, implode(',', $in))->delete($detailtable);
        } else {
            $this->where($detailField, $id[0]->$masterField)->delete($detailtable);
        }

        return $this;
    }

    public function insert($tblname, $insert = array()) {
        if (is_array($insert)) {
            foreach ($insert as $k => $v) {
                $key[] = $k;
                $val[] = $v;
            }

            $rowname = implode(',', $key);
            $countkeys = count($key);
            $rowvalue = "";
            for ($i = 0; $countkeys > $i; $i++) {
                $rowvalue.='?, ';
            }

            $rowvalue = substr($rowvalue, 0, -2);

            $sql = "INSERT INTO $tblname ($rowname) VALUES ($rowvalue)";
            try {
                $q = $this->db_conn->prepare($sql);
                if (!$q->execute($val)) {
                    $this->show_err($sql, $this->db_conn->errorInfo());
                }
                $this->queryStatus = boolval($q);
                $this->lastinsertid = $this->db_conn->lastInsertId();
            } catch (PDOException $e) {
                $this->show_err($sql, $e->getMessage());
            } //try catch end;
            $this->lastsql = $sql;
        }
        return $this;
    }

    public function insertOrUpdate($tblname, $insert = array(), $uniqkey = 'ID') {
        if (is_array($insert)) {
            foreach ($insert as $k => $v) {
                $key[] = $k;
                $val[] = $v;
            }

            $rowname = implode(',', $key);
            $countkeys = count($key);
            $rowvalue = "";
            $duplicatekey = '';
            for ($i = 0; $countkeys > $i; $i++) {
                $rowvalue.='?, ';
            }

            $rowvalue = substr($rowvalue, 0, -2);

            foreach ($key as $q) {
                if ($q != $uniqkey) {
                    $duplicatekey.="$q=VALUES($q),";
                }
            }

            $duplicatekey = substr($duplicatekey, 0, -1);

            $sql = "INSERT INTO $tblname ($rowname) VALUES ($rowvalue) ON DUPLICATE KEY UPDATE $duplicatekey";
            try {
                $q = $this->db_conn->prepare($sql);
                if (!$q->execute($val)) {
                    $this->show_err($sql, $this->db_conn->errorInfo());
                }
                $this->queryStatus = boolval($q);
                $this->lastinsertid = $this->db_conn->lastInsertId();
            } catch (PDOException $e) {
                $this->show_err($sql, $e->getMessage());
            } //try catch end;
            $this->lastsql = $sql;
        }
        return $this;
    }

    public function lastinsertid() {
        return $this->lastinsertid;
    }

    public function delete($tblname, $where = 1) {
        if ($this->countwhere) {
            $where = $this->where;
        } else {
            $where = "WHERE $where";
        }
        $sql = "DELETE FROM $tblname " . $where;

        try {
            $q = $this->db_conn->prepare($sql);
            $this->queryStatus = boolval($q);
            if (!$q->execute($this->prepare)) {
                $this->show_err($sql, $this->db_conn->errorInfo());
            }
        } catch (PDOException $e) {
            $this->show_err($sql, $e->getMessage());
        } //try catch end;
        $this->lastsql = $sql;
        $this->where = 'where ';
        $this->countwhere = 0;
        $this->prepare = array();
        return $this;
    }

    public function update($tblname, $update = array(), $where = NULL) {
        $upd = '';
        if (is_array($update)) {
            foreach ($update as $k => $v) {
                $upd.="$k=:$k, ";
                $val[$k] = $v;
            } //foreach update end;


            $upd = substr($upd, 0, -2);
            $wher = '';
            if ($where !== NULL) {
                $wher = "where $where";
            }
            $val = array_merge($val, $this->prepare);
            $sql = "UPDATE $tblname SET $upd " . ($this->countwhere ? $this->where : $where);
            try {
                $q = $this->db_conn->prepare($sql);
                $this->queryStatus = boolval($q);
                if (!$q->execute($val)) {
                    $this->show_err($sql, $this->db_conn->errorInfo());
                }
            } catch (PDOException $e) {
                $this->show_err($sql, $e->getMessage());
            } //try catch end;
        }
        $this->lastsql = $sql;
        $this->where = 'where ';
        $this->countwhere = 0;
        $this->prepare = array();
        return $this;
    }

    public function beginTransaction() {
        $this->db_conn->beginTransaction();
    }

    public function commit() {
        $this->db_conn->commit();
    }

    public function rollback() {
        $this->db_conn->rollback();
    }

    public function where($row, $val = NULL) {

        if (is_array($row)) {
            foreach ($row as $k => $v) {
                if (!$this->countwhere) {
                    $this->where.="$k=:$k ";
                    $this->prepare[$k] = $v;
                } else {
                    $this->where.="and $k=:$k ";
                    $this->prepare[$k] = $v;
                }// if countwhere end;
                $this->countwhere++;
            }//foreach row end;
        } else {
            if (!$this->countwhere) {
                $this->where.="$row=:$row ";
                $this->prepare[$row] = $val;
            } else {
                if ($this->delimiter && !$this->delimiterCount) {
                    $this->where = rtrim($this->where, '( ') . " and ( $row=:$row ";
                    $this->prepare[$row] = $val;
                    $this->delimiterCount++;
                } else {
                    $this->where.="and $row=:$row ";
                    $this->prepare[$row] = $val;
                }
            }// if countwhere end;
            $this->countwhere++;
        }// if is_array end;


        return $this;
    }

    public function join($tablename, $where) {

        $this->join.= ' INNER JOIN ' . $tablename . ' ON ' . $where;
        return $this;
    }

    public function join_left($tablename, $where) {

        $this->join.= ' LEFT JOIN ' . $tablename . ' ON ' . $where;
        return $this;
    }

    public function join_right($tablename, $where) {

        $this->join.= ' RIGHT JOIN ' . $tablename . ' ON ' . $where;
        return $this;
    }

    public function where_or($row, $val = NULL) {

        if (is_array($row)) {
            foreach ($row as $k => $v) {
                if (!$this->countwhere) {
                    $this->where.="$k=:$k ";
                    $this->prepare[$k] = $v;
                } else {
                    $this->where.="or $k=:$k ";
                    $this->prepare[$k] = $v;
                }// if countwhere end;
            }//foreach row end;
        } else {
            if (!$this->countwhere) {
                $this->where.="$row=:$row ";
                $this->prepare[$row] = $val;
            } else {

                if ($this->delimiter && !$this->delimiterCount) {
                    $this->where = rtrim($this->where, '( ') . " or ( $row=:$row ";
                    $this->prepare[$row] = $val;
                    $this->delimiterCount++;
                } else {
                    $this->where.="or $row=:$row ";
                    $this->prepare[$row] = $val;
                }
            }// if countwhere end;
        }// if is_array end;

        $this->countwhere++;
        return $this;
    }

    public function where_static($where) {
        if (!$this->countwhere) {
            $this->where.=$where . " ";
        } else {
            $this->where.="and $where ";
        }// if countwhere end;

        $this->countwhere++;
        return $this;
    }

    public function where_in($row, $val = NULL, $clause = 'and') {

        if (is_array($row)) {
            foreach ($row as $k => $v) {
                if (!$this->countwhere) {
                    $this->where.="$k in (:$k) ";
                    $this->prepare[$k] = $v;
                } else {
                    $this->where.="$clause $k in (:$k) ";
                    $this->prepare[$k] = $v;
                }// if countwhere end;
            }//foreach row end;
        } else {
            if (!$this->countwhere) {
                $this->where.="$row in (:$row) ";
                $this->prepare[$row] = $val;
            } else {
                $this->where.="$clause $row in (:$row) ";
                $this->prepare[$row] = $val;
            }// if countwhere end;
        }// if is_array end;

        $this->countwhere++;
        return $this;
    }

    public function addDelimiter($delimiter = '(') {
        $this->where.=$delimiter . ' ';
        $this->delimiter = true;
        return $this;
    }

    public function endDelimiter($delimiter = ')') {
        $this->where.=$delimiter . ' ';
        $this->delimiter = false;
        $this->delimiterCount = 0;
        return $this;
    }

    public function where_like($row, $val = NULL, $clause = 'and') {

        if (is_array($row)) {
            foreach ($row as $k => $v) {
                if (!$this->countwhere) {
                    $this->where.="$k like '%:$k%' ";
                    $this->prepare[$k] = $v;
                } else {
                    $this->where.="$clause $k like '%:$k%' ";
                    $this->prepare[$k] = $v;
                }// if countwhere end;
            }//foreach row end;
        } else {
            if (!$this->countwhere) {
                $this->where.="$row like '%:$row%' ";
                $this->prepare[$row] = $val;
            } else {
                $this->where.="$clause $row like '%:$row%' ";
                $this->prepare[$row] = $val;
            }// if countwhere end;
        }// if is_array end;

        $this->countwhere++;
        return $this;
    }

    public function where_between($row, $val1 = NULL, $val2 = NULL, $clause = 'and') {
        if (!$this->countwhere) {
            $this->where.="$row between :$row" . "1 and :$row" . "2 ";
            $this->prepare[$row . '1'] = $val1;
            $this->prepare[$row . '2'] = $val2;
        } else {
            $this->where.="$clause $row between :$row" . "1 and :$row" . "2 ";
            $this->prepare[$row . '1'] = $val1;
            $this->prepare[$row . '2'] = $val2;
        }// if countwhere end;

        $this->countwhere++;
        return $this;
    }

    public function from($from) {
        if ($this->fromcount > 0) {
            $this->from.=',' . $from;
        } else {
            $this->from.=$from;
        }

        $this->fromcount++;
        return $this;
    }

    public function select($select) {
        if ($this->selectcount > 0) {
            $this->select.=',' . $select;
        } else {
            $this->select.=$select;
        }

        $this->selectcount++;
        return $this;
    }

    public function groupby($groub) {
        if ($this->groubcount > 0) {
            $this->groub.=',' . $groub . ' ';
        } else {
            $this->groub.='GROUP BY ' . $groub . ' ';
        }

        $this->groubcount++;
        return $this;
    }

    public function having($having, $clause = 'and') {
        if ($this->havingcount > 0) {
            $this->having.=$clause . $having . ' ';
        } else {
            $this->having.='HAVING ' . $having . ' ';
        }

        $this->havingcount++;
        return $this;
    }

    public function get($tblname = NULL) {
        if ($tblname !== NULL) {
            $this->from = $tblname;
        }

        if ($this->select == '')
            $this->select = '*';

        $sql = 'select ' . $this->select . ' from ' . $this->from . ' ' . $this->join . ' ' . ($this->countwhere ? $this->where : ' ') . $this->groub . $this->having . $this->orderby . $this->limit;

        $this->where = 'where ';
        $this->countwhere = 0;
        $this->groub = '';
        $this->join = '';
        $this->groubcount = 0;
        $this->select = '';
        $this->selectcount = 0;
        $this->from = '';
        $this->fromcount = 0;
        $this->orderbycount = 0;
        $this->orderby = '';
        $this->limit = '';
        $this->havingcount = 0;
        $this->having = '';
        $this->getFetchColumn = false;
        $this->getFetchColumnNumber = 0;


        return $this->query($sql);
    }

    public function get_sql($clearSql = FALSE) {

        if ($clearSql) {
            $this->where = 'where ';
            $this->countwhere = 0;
            $this->groub = '';
            $this->join = '';
            $this->groubcount = 0;
            $this->select = '';
            $this->selectcount = 0;
            $this->from = '';
            $this->fromcount = 0;
            $this->orderbycount = 0;
            $this->havingcount = 0;
            $this->having = '';
            $this->getFetchColumn = false;
            $this->getFetchColumnNumber = 0;
        }

        return $this->lastsql;
    }

    public function orderby($orderby, $type = 'asc') {
        if ($this->orderbycount > 0) {
            $this->orderby.=',' . $orderby . ' ' . $type . ' ';
        } else {
            $this->orderby.='order by ' . $orderby . ' ' . $type . ' ';
        }
        $this->orderbycount++;
        return $this;
    }

    public function limit($limit, $limit2 = NULL) {
        if ($limit2 !== NULL) {
            $this->limit = "limit " . $limit . ',' . $limit2;
        } else {
            $this->limit = "limit " . $limit;
        }
        return $this;
    }

    public function show_err($sql, $sqlerr) {
        echo '<div><strong>SQL:</strong> ' . $sql . '<br>';
        echo '<strong>SQL Error:</strong> ' . $sqlerr . '<div>';
        return FALSE;
    }

    public function rowcount() {
        return $this->rowcount;
    }

    private function paramsToSql($query, $params) {
        $keys = array();
        $values = $params;

        # build a regular expression for each parameter
        foreach ($params as $key => $value) {
            if (is_string($key)) {
                $keys[] = '/:' . $key . '/';
            } else {
                $keys[] = '/[?]/';
            }

            if (is_string($value))
                $values[$key] = "'" . $value . "'";

            if (is_array($value))
                $values[$key] = implode(',', $value);

            if (is_null($value))
                $values[$key] = 'NULL';
        }

        $query = preg_replace($keys, $values, $query, 1, $count);

        return $query;
    }

    public function debug() {
        header('Content-Type: text/html; charset=utf-8');
        $sql = $this->lastsql;
        $lastprepare = $this->lastprepare;
        $colname = $this->showtablefields($this->paramsToSql($sql, $lastprepare), TRUE);
        $colcount = $this->rowCount();
        $result = $this->query($this->paramsToSql($sql, $lastprepare));
        echo '<strong> Last Sql: </strong>' . $this->paramsToSql($sql, $lastprepare) . '<br>';
        echo '<small><strong> Last Sql Prepare Before: </strong>' . $sql . '</small><br>';
        echo '<small><strong> Last Sql Prepare Value: </strong><pre>' . print_r($lastprepare, TRUE) . '</pre></small><br>';
        echo '<table cellspacing=0 border=1 style="font-size:9px;"><tr>';
        echo '<th style="padding:3px; border:1px solid #333;border-collapse:collapse; background-color:#ccc">Row No</th>';
        $i = 1;
        foreach ($colname as $q) {
            echo '<th style="padding:3px; border:1px solid #333;border-collapse:collapse; background-color:#ccc">' . $q->name . '</th>';
        }
        echo '</tr>';
        if ($this->rowCount()) {
            foreach ($result as $q) {
                echo '<tr>';
                echo '<td style="border:1px solid #333;">&nbsp;' . $i . '</td>';
                foreach ($colname as $w) {
                    $coln = $w->name;
                    echo '<td style="border:1px solid #333;">&nbsp;' . $q->$coln . '</td>';
                }
                echo '</tr>';
                $i++;
            }
        } else {
            echo '<tr><td colspan="' . ($colcount + 1) . '">No data.</td></tr>';
        }
        echo '</table>';
    }

    function __desctruct() {
        $this->db_conn = NULL;
    }

// function __desctruct end;
}

/**
 * Try alternative way to test for bool value
 *
 * @param mixed
 * @param bool
 */
if (!function_exists('boolval')) {

    function boolval($BOOL, $STRICT = false) {

        if (is_string($BOOL)) {
            $BOOL = strtoupper($BOOL);
        }

        // no strict test, check only against false bool
        if (!$STRICT && in_array($BOOL, array(false, 0, NULL, 'FALSE', 'NO', 'N', 'OFF', '0'), true)) {

            return false;

            // strict, check against true bool
        } elseif ($STRICT && in_array($BOOL, array(true, 1, 'TRUE', 'YES', 'Y', 'ON', '1'), true)) {

            return true;
        }

        // let PHP decide
        return $BOOL ? true : false;
    }

}
