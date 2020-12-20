<?php

namespace MillenniumFalcon\Core\Controller\Traits\Cms\Core;

use App\ORM\Order;
use App\ORM\OrderItem;
use BlueM\Tree;
use MillenniumFalcon\Core\ORM\_Model;
use MillenniumFalcon\Core\ORM\FormDescriptor;
use MillenniumFalcon\Core\ORM\FormSubmission;
use MillenniumFalcon\Core\Service\ModelService;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use MillenniumFalcon\Core\Tree\RawData;

trait CmsCoreOrmsTrait
{
    /**
     * @route("/manage/admin/orms/Page")
     * @return Response
     */
    public function pages(Request $request)
    {
        $params = $this->getCmsTemplateParams($request);

        $model = _Model::getByField($this->connection, 'className', 'Page');
        $params['ormModel'] = $model;

        $fullClass = ModelService::fullClass($this->connection, 'PageCategory');
        $categories = $fullClass::active($this->connection);
        $cat = $request->get('cat') || $request->get('cat') === '0' ? $request->get('cat') : (count($categories) == 0 ? 0 : $categories[0]->getId());
        $params['categories'] = $categories;
        $params['cat'] = $cat;

        return $this->render($params['theNode']->template, $params);
    }

    /**
     * @route("/manage/orms/FormSubmission")
     * @route("/manage/admin/orms/FormSubmission")
     * @return Response
     */
    public function formSubmissions(Request $request)
    {
        $params = $this->getCmsTemplateParams($request);

        $fullClass = ModelService::fullClass($this->connection, 'FormDescriptor');
        $params['formDescriptors'] = $fullClass::active($this->connection, [
            'sort' => 'm.title'
        ]);
        $params['formDescriptors'] = array_map(function ($itm) {
            $fullClass = ModelService::fullClass($this->connection, 'FormSubmission');
            $total = $fullClass::data($this->connection, [
                'count' => 1,
                'whereSql' => 'm.formDescriptorId = ?',
                'params' => [$itm->getId()],
            ]);
            $itm->_count = $total['count'];
            return $itm;
        }, $params['formDescriptors']);

        $params['filterFormDescriptor'] = $fullClass::getBySlug($this->connection, $request->get('form'));
        $params['filterStart'] = $request->get('start');
        $params['filterEnd'] = $request->get('end');
        $params['filterFormat'] = $request->get('format') ?: 1;


        $model = _Model::getByField($this->connection, 'className', 'FormSubmission');
        $params['ormModel'] = $model;

        $fullClass = ModelService::fullClass($this->connection, $model->getClassName());

        $pageNum = $request->get('pageNum') ?: 1;
        $sort = $request->get('sort') ?: $model->getDefaultSortBy();
        $order = $request->get('order') ?: ($model->getDefaultOrder() == 0 ? 'ASC' : 'DESC');
        $keyword = $request->get('keyword') ?: '';
        $status = $request->get('status') ?: 0;


        $sqlWhere = '';
        $sqlParams = [];

        if ($params['filterFormDescriptor']) {
            $sqlWhere .= ($sqlWhere ? ' AND ' : '') . '(m.formDescriptorId = ?)';
            $sqlParams = array_merge($sqlParams, [
                $params['filterFormDescriptor']->getId(),
            ]);
        }

        if ($params['filterStart']) {
            $sqlWhere .= ($sqlWhere ? ' AND ' : '') . '(m.added >= ?)';
            $sqlParams = array_merge($sqlParams, [
                date('Y-m-d 00:00:00', strtotime($params['filterStart']))
            ]);
        }

        if ($params['filterEnd']) {
            $sqlWhere .= ($sqlWhere ? ' AND ' : '') . '(m.added <= ?)';
            $sqlParams = array_merge($sqlParams, [
                date('Y-m-d 23:59:59', strtotime($params['filterEnd']))
            ]);
        }


        if ($request->isMethod('POST') && $params['filterFormDescriptor']) {
            $submitValue = $request->get('submit');
            if ($submitValue == 'Export') {
                /** @var FormSubmission[] $orms */
                $orms = $fullClass::data($this->connection, [
                    "whereSql" => $sqlWhere,
                    "params" => $sqlParams,
                    "sort" => $sort,
                    "order" => $order,
                ]);

                /** @var FormDescriptor $filterFormDescriptor */
                $filterFormDescriptor = $params['filterFormDescriptor'];
                $formFileds = json_decode($filterFormDescriptor->getFormFields());
                $filename = $filterFormDescriptor->getSlug() . '-export-' . date('Y-m-d-H-i-s');

                if ($params['filterFormat'] == 1) {
                    $data = [];

                    $header = [];
                    $header[] = 'Date';
                    foreach ($formFileds as $idx => $itm) {
                        $header[] = $itm->label;
                    }
                    $data[] = $header;

                    foreach ($orms as $formSubmission) {
                        $row = [];
                        $row[] = date('d M Y@H:i:s', strtotime($formSubmission->getAdded()));

                        $jsonContent = json_decode($formSubmission->getContent());
                        foreach ($jsonContent as $idx => $itm) {
                            if ($itm[0] == 'antispam') {
                                continue;
                            }
                            if (gettype($itm[1]) == 'array' || gettype($itm[1]) == 'object') {
                                $itm[1] = (array)$itm[1];
                                $itm[1] = implode(',', $itm[1]);
                            }
                            $row[] = $itm[1];
                        }
                        $data[] = $row;
                    }

                    $this->download_send_headers($filename . ".csv");
                    echo $this->array2csv($data);
                    die();

                } else if ($params['filterFormat'] == 2) {

                    $phpExcel = new Spreadsheet();
                    $sheet = $phpExcel->getActiveSheet();

                    $count = 1;
                    $sheet->setCellValue(Coordinate::stringFromColumnIndex(1) . "{$count}", 'Date');
                    foreach ($formFileds as $idx => $itm) {
                        $sheet->setCellValue(Coordinate::stringFromColumnIndex($idx + 2) . "{$count}", $itm->label);
                    }

                    $count = 2;
                    foreach ($orms as $formSubmission) {
                        $sheet->setCellValue(Coordinate::stringFromColumnIndex(1) . "{$count}", date('d M Y@H:i:s', strtotime($formSubmission->getAdded())));
                        $jsonContent = json_decode($formSubmission->getContent());
                        foreach ($jsonContent as $idx => $itm) {
                            if ($itm[0] == 'antispam') {
                                continue;
                            }
                            if (gettype($itm[1]) == 'array' || gettype($itm[1]) == 'object') {
                                $itm[1] = (array)$itm[1];
                                $itm[1] = implode(',', $itm[1]);
                            }
                            $sheet->setCellValue(Coordinate::stringFromColumnIndex($idx + 2) . "{$count}", $itm[1]);
                        }
                        $count++;
                    }

                    $writer = IOFactory::createWriter($phpExcel, 'Xlsx');
                    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                    header('Content-Disposition: attachment; filename="' . $filename . '.xlsx' . '"');
                    $writer->save('php://output');
                    exit;
                }
            }
        }

        $orms = $fullClass::data($this->connection, [
            "whereSql" => $sqlWhere,
            "params" => $sqlParams,
            "page" => $pageNum,
            "limit" => $model->getNumberPerPage(),
            "sort" => $sort,
            "order" => $order,
        ]);

        $total = $fullClass::data($this->connection, [
            "whereSql" => $sqlWhere,
            "params" => $sqlParams,
            "count" => 1,
        ]);

        $params['total'] = $total['count'];
        $params['totalPages'] = ceil($total['count'] / $model->getNumberPerPage());
        $params['url'] = $request->getPathInfo() . "?sort=$sort&order=$order&form={$request->get('form')}&start={$request->get('start')}&end={$request->get('end')}&format={$params['filterFormat']}";
        $params['urlNoSort'] = $request->getPathInfo();
        $params['pageNum'] = $pageNum;
        $params['sort'] = $sort;
        $params['order'] = $order;
        $params['orms'] = $orms;

        return $this->render($params['theNode']->template, $params);
    }

    /**
     * @Route("/manage/orms/Redirect")
     * @param Request $request
     * @return Response
     */
    public function redirects(Request $request)
    {
        $params = $this->getCmsTemplateParams($request);

        $model = _Model::getByField($this->connection, 'className', 'Redirect');
        $params['ormModel'] = $model;

        $fullClass = ModelService::fullClass($this->connection, $model->getClassName());

        $pageNum = $request->get('pageNum') ?: 1;
        $sort = $request->get('sort') ?: $model->getDefaultSortBy();
        $order = $request->get('order') ?: ($model->getDefaultOrder() == 0 ? 'ASC' : 'DESC');
        $keyword = $request->get('keyword') ?: '';
        $status = $request->get('status') ?: 0;

        $params['filterStatus'] = $status;
        $params['filterKeyword'] = $keyword;

        $sqlWhere = '';
        $sqlParams = [];

        if ($status != 0) {
            $sqlWhere .= ($sqlWhere ? ' AND ' : '') . '(m.status = ?)';
            $sqlParams = array_merge($sqlParams, [
                $status == 1 ? 1 : 0
            ]);
        }

        if ($keyword) {
            $sqlWhere .= ($sqlWhere ? ' AND ' : '') . '(m.title LIKE ? OR m.to LIKE ?)';
            $sqlParams = array_merge($sqlParams, [
                "%$keyword%", "%$keyword%"
            ]);
        }

        $orms = $fullClass::data($this->connection, [
            "whereSql" => $sqlWhere,
            "params" => $sqlParams,
            "page" => $pageNum,
            "limit" => $model->getNumberPerPage(),
            "sort" => $sort,
            "order" => $order,
        ]);

        $total = $fullClass::data($this->connection, [
            "whereSql" => $sqlWhere,
            "params" => $sqlParams,
            "count" => 1,
        ]);

        $params['total'] = $total['count'];
        $params['totalPages'] = ceil($total['count'] / $model->getNumberPerPage());
        $params['url'] = $request->getPathInfo() . "?sort=$sort&order=$order";
        $params['urlNoSort'] = $request->getPathInfo();
        $params['pageNum'] = $pageNum;
        $params['sort'] = $sort;
        $params['order'] = $order;
        $params['orms'] = $orms;

        return $this->render($params['theNode']->template, $params);
    }

    /**
     * @Route("/manage/orms/Order")
     * @param Request $request
     * @return Response
     */
    public function orders(Request $request)
    {
        $params = $this->getCmsTemplateParams($request);

        $model = _Model::getByField($this->connection, 'className', 'Order');
        $params['ormModel'] = $model;

        $fullClass = ModelService::fullClass($this->connection, $model->getClassName());

        $pageNum = $request->get('pageNum') ?: 1;
        $sort = $request->get('sort') ?: $model->getDefaultSortBy();
        $order = $request->get('order') ?: ($model->getDefaultOrder() == 0 ? 'ASC' : 'DESC');
        $keyword = $request->get('keyword') ?: '';
        $status = $request->get('status') ?: 0;

//        $params['filterStatus'] = $status;
//        $params['filterKeyword'] = $keyword;

        $sqlWhere = 'm.category != ?';
        $sqlParams = [0];

//        if ($status != 0) {
//            $sqlWhere .= ($sqlWhere ? ' AND ' : '') . '(m.status = ?)';
//            $sqlParams = array_merge($sqlParams, [
//                $status == 1 ? 1 : 0
//            ]);
//        }
//
//        if ($keyword) {
//            $sqlWhere .= ($sqlWhere ? ' AND ' : '') . '(m.title LIKE ? OR m.to LIKE ?)';
//            $sqlParams = array_merge($sqlParams, [
//                "%$keyword%", "%$keyword%"
//            ]);
//        }

        $orms = $fullClass::data($this->connection, [
            "whereSql" => $sqlWhere,
            "params" => $sqlParams,
            "page" => $pageNum,
            "limit" => $model->getNumberPerPage(),
            "sort" => $sort,
            "order" => $order,
        ]);

        $total = $fullClass::data($this->connection, [
            "whereSql" => $sqlWhere,
            "params" => $sqlParams,
            "count" => 1,
        ]);

        $params['total'] = $total['count'];
        $params['totalPages'] = ceil($total['count'] / $model->getNumberPerPage());
        $params['url'] = $request->getPathInfo() . "?sort=$sort&order=$order";
        $params['urlNoSort'] = $request->getPathInfo();
        $params['pageNum'] = $pageNum;
        $params['sort'] = $sort;
        $params['order'] = $order;
        $params['orms'] = $orms;

        return $this->render($params['theNode']->template, $params);
    }

    /**
     * @route("/manage/orms/{className}")
     * @route("/manage/admin/orms/{className}")
     * @route("/manage/pages/orms/{className}")
     * @return Response
     */
    public function orms(Request $request, $className)
    {
        $params = $this->getCmsTemplateParams($request);

        $model = _Model::getByField($this->connection, 'className', $className);
        $params['ormModel'] = $model;

        $fullClass = ModelService::fullClass($this->connection, $model->getClassName());
        if ($model->getListType() == 0) {
            $orms = $fullClass::data($this->connection);

            $total = $fullClass::data($this->connection, [
                "count" => 1,
            ]);
            $params['total'] = $total['count'];

        } elseif ($model->getListType() == 1) {

            $pageNum = $request->get('pageNum') ?: 1;
            $sort = $request->get('sort') ?: $model->getDefaultSortBy();
            $order = $request->get('order') ?: ($model->getDefaultOrder() == 0 ? 'ASC' : 'DESC');

            $orms = $fullClass::data($this->connection, [
                "page" => $pageNum,
                "limit" => $model->getNumberPerPage(),
                "sort" => $sort,
                "order" => $order,
            ]);

            $total = $fullClass::data($this->connection, [
                "count" => 1,
            ]);

            $params['total'] = $total['count'];
            $params['totalPages'] = ceil($total['count'] / $model->getNumberPerPage());
            $params['url'] = $request->getPathInfo() . "?sort=$sort&order=$order";
            $params['urlNoSort'] = $request->getPathInfo();
            $params['pageNum'] = $pageNum;
            $params['sort'] = $sort;
            $params['order'] = $order;

        } elseif ($model->getListType() == 2) {
            $sort = $request->get('sort') ?: $model->getDefaultSortBy();
            $order = $request->get('order') ?: ($model->getDefaultOrder() == 0 ? 'ASC' : 'DESC');

            $result = $fullClass::data($this->connection, [
                "sort" => $sort,
                "order" => $order,
            ]);

            $nodes = [];
            foreach ($result as $itm) {
                $nodes[] = (array)new RawData([
                    'id' => $itm->getId(),
                    'parent' => $itm->getParentId(),
                    'title' => $itm->getTitle(),
                    'url' => '/manage/' . ($model->getDataType() == 2 ? 'admin' : '') . '/orms/' . $model->getClassName() . '/',
                    'template' => $fullClass::getCmsOrmTwig(),
                    'status' => $itm->getStatus(),
                    'allowExtra' => 1,
                    'maxParams' => 3,
                    'closed' => $itm->getClosed(),
                ]);
            }

            $tree = new Tree($nodes, [
                'rootId' => null,
                'buildwarningcallback' => function () {},
            ]);
            $orms = $tree->getRootNodes();
        }
        $params['orms'] = $orms;

        return $this->render($params['theNode']->template, $params);
    }

    /**
     * @param $filename
     */
    protected function download_send_headers($filename) {
        // disable caching
        $now = gmdate("D, d M Y H:i:s");
        header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
        header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
        header("Last-Modified: {$now} GMT");

        // force download
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");

        // disposition / encoding on response body
        header("Content-Disposition: attachment;filename={$filename}");
        header("Content-Transfer-Encoding: binary");
    }

    /**
     * @param array $array
     * @return false|string|null
     */
    protected function array2csv(array &$array)
    {
        if (count($array) == 0) {
            return null;
        }
        ob_start();
        $df = fopen("php://output", 'w');
        fputcsv($df, array_keys(reset($array)));
        foreach ($array as $row) {
            fputcsv($df, $row);
        }
        fclose($df);
        return ob_get_clean();
    }
}