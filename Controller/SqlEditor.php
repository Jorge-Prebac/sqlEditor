<?php
/**
 * Con este plugin podrás ejecutar sentencias sql que muestren datos en pantalla
 * sentencias tipo select, show, etc.
 * Filtrará para evitar la ejecución de sentencias tipo Drop, delete, alter, etc.
 * @author Raul Jiménez <raljopa@gmail.com>
 */
namespace FacturaScripts\Plugins\SqlEditor\Controller;

use FacturaScripts\Core\Base;

class SqlEditor extends \FacturaScripts\Core\Base\Controller
{

    public $query;
    public $results;
    public $columnsTable;

    /**
     * Return the basic data for this page.
     *
     * @return array
     */
    public function getPageData(): Array
    {
        $pageData = parent::getPageData();
        $pageData['title'] = 'sql Editor';
        $pageData['menu'] = 'develop';
        $pageData['icon'] = 'fas fa-database';

        return $pageData;
    }

    /**
     * Execute action
     *
     * @param string $action
     * @return boolean
     */
    private function executeAction($action)
    {
        switch ($action) {
            case 'getFields':
                $tableName = $this->request->get('table');
                if ($tableName !== '') {
                    $this->columnsTable = $this->getColumns($tableName);
                    $this->setTemplate(false);
                    $this->response->setContent(json_encode(array_keys($this->columnsTable)));
                }
                break;
            default:
                return FALSE;
                break;
        }
    }

    /**
     * Runs the controller's private logic.
     *
     * @param Response              $response
     * @param User                  $user
     * @param ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permisions)
    {
        parent::privateCore($response, $user, $permisions);
        $this->setTemplate('sqlEditor');

        $query = $this->request->get('query', '');

        if ($query !== '') {
            if ($this->user->admin) {
                $this->query = $query;

                if ($this->validQuery()) {
                    $this->execQuery();
                } else {
                    $this->miniLog->alert($this->i18n->trans('invalid-sentence'));
                    $this->results = [];
                }
            } else {
                $this->miniLog->aler('only-admin');
            }
        }
        $action = $this->request->request->get('action');
        if ($action) {
            $this->executeAction($action);
        }
    }

    /**
     * Return true is is authorized query
     *
     * @return boolean
     */
    private function validQuery()
    {

        $validSentences = ['select', 'show', 'describe', 'explain'];
        $invalidSentences = ['drop', 'delete', 'insert', 'update'];

        foreach ($invalidSentences as $sentence) {
            if (strpos($this->query, $sentence) !== FALSE) {

                return false;
            }
        }

        foreach ($validSentences as $sentence) {

            if (strpos($this->query, $sentence) !== FALSE) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns array width result values of exec query
     * @param string $query
     *
     *
     */
    private function execQuery()
    {
        $dataBase = new \FacturaScripts\Core\Base\DataBase();
        $data = $dataBase->select($this->query);

        if ($data) {
            $this->results = $data;
        } else {
            $this->miniLog->notice($this->i18n->trans('without-results'));
        }
    }

    /**
     * Returns array width columns title of results
     * @return Array
     */
    public function columnsTitle(): Array
    {
        return empty($this->results) ? [] : array_keys($this->results[0]);
    }

    /**
     * Return Tablenames of database
     * @return Array
     */
    public function getTables(): Array
    {

        $dataBase = new \FacturaScripts\Core\Base\DataBase();
        return $dataBase->getTables();
    }

    /**
     * Return column names of table
     * @param string $tableName
     * @return Array
     */
    public function getColumns($tableName = ''): Array
    {
        if ($tablename !== '') {
            $dataBase = new \FacturaScripts\Core\Base\DataBase();
            return $dataBase->getColumns($tableName);
        } else {
            return [];
        }
    }
}
