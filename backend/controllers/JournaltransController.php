<?php

namespace backend\controllers;

use Exception;
use Yii;
use backend\models\JournalTrans;
use backend\models\journalTransSearch;
use common\models\JournalTransLine;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\Response;
use yii\widgets\ActiveForm;

/**
 * JournaltransController implements the CRUD actions for JournalTrans model.
 */
class JournaltransController extends Controller
{
    public $enableCsrfValidation = false;
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                    'delete-line' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all JournalTrans models.
     * @return mixed
     */
    public function actionIndex()
    {
        $pageSize = \Yii::$app->request->post("perpage");
        $searchModel = new JournalTransSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->setSort(['defaultOrder' => ['id' => SORT_ASC]]);
        $dataProvider->pagination->pageSize = $pageSize;

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'perpage' => $pageSize,
        ]);
    }

    /**
     * Displays a single JournalTrans model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        $lines = $model->journalTransLines;

        return $this->render('view', [
            'model' => $model,
            'lines' => $lines,
        ]);
    }

    /**
     * Creates a new JournalTrans model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate($type = null)
    {
        $model = new JournalTrans();
        $modelLines = [new JournalTransLine()];

        if ($model->load(\Yii::$app->request->post())) {
            $modelLines = $this->createMultiple(JournalTransLine::class);
            JournalTransLine::loadMultiple($modelLines, \Yii::$app->request->post());

            // Ajax validation
            if (\Yii::$app->request->isAjax) {
                \Yii::$app->response->format = Response::FORMAT_JSON;
                return ArrayHelper::merge(
                    ActiveForm::validateMultiple($modelLines),
                    ActiveForm::validate($model)
                );
            }

            // Validate all models
            $valid = $model->validate();
           // $valid = JournalTransLine::validateMultiple($modelLines) && $valid;

            if ($valid) {
                $transaction = \Yii::$app->db->beginTransaction();
                try {
                    if ($flag = $model->save(false)) {
                        foreach ($modelLines as $modelLine) {
                            $modelLine->journal_trans_id = $model->id;
                            if (!($flag = $modelLine->save(false))) {
                                break;
                            }
                        }
                    }

                    if($flag) {
                        foreach ($modelLines as $modelLine) {
                            $model_stock_trans = new \common\models\StockTrans();
                            $model_stock_trans->trans_date = $model->trans_date;
                            $model_stock_trans->journal_trans_id = $model->id;
                            $model_stock_trans->trans_type_id = $model->trans_type_id;
                            $model_stock_trans->product_id = $modelLine->product_id;
                            $model_stock_trans->qty = $modelLine->qty;
                            $model_stock_trans->warehouse_id = $modelLine->warehouse_id;
                            $model_stock_trans->stock_type_id = $model->stock_type_id;
                            $model_stock_trans->remark = $modelLine->remark;
                            $model_stock_trans->created_by = $model->created_by;
                            $model_stock_trans->save(false);
                        }

                    }

                    if ($flag) {
                        $transaction->commit();
                        Yii::$app->session->setFlash('success', 'บันทึกข้อมูลสำเร็จ');
                        return $this->redirect(['view', 'id' => $model->id]);
                    }
                } catch (Exception $e) {
                    $transaction->rollBack();
                    Yii::$app->session->setFlash('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
                }
            }
        }

        return $this->render('create', [
            'model' => $model,
            'modelLines' => (empty($modelLines)) ? [new JournalTransLine()] : $modelLines,
            'create_type' => $type,
        ]);
    }

    /**
     * Updates an existing JournalTrans model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $modelLines = $model->journalTransLines;

        if ($model->load(Yii::$app->request->post())) {

            $oldIDs = ArrayHelper::map($modelLines, 'id', 'id');
            $modelLines = $this->createMultiple(JournalTransLine::class, $modelLines);
            JournalTransLine::loadMultiple($modelLines, Yii::$app->request->post());
            $deletedIDs = array_diff($oldIDs, array_filter(ArrayHelper::map($modelLines, 'id', 'id')));

            // Ajax validation
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ArrayHelper::merge(
                    ActiveForm::validateMultiple($modelLines),
                    ActiveForm::validate($model)
                );
            }

            // Validate all models
            $valid = $model->validate();
            //$valid = JournalTransLine::validateMultiple($modelLines) && $valid;

            if ($valid) {
                $transaction = Yii::$app->db->beginTransaction();
                try {
                    if ($flag = $model->save(false)) {
                        if (!empty($deletedIDs)) {
                            JournalTransLine::deleteAll(['id' => $deletedIDs]);
                        }
                        foreach ($modelLines as $modelLine) {
                            $modelLine->journal_trans_id = $model->id;
                            if (!($flag = $modelLine->save(false))) {
                                break;
                            }
                        }
                    }

                    if ($flag) {
                        $transaction->commit();
                        Yii::$app->session->setFlash('success', 'บันทึกข้อมูลสำเร็จ');
                        return $this->redirect(['view', 'id' => $model->id]);
                    }
                } catch (Exception $e) {
                    $transaction->rollBack();
                    Yii::$app->session->setFlash('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
                }
            }
        }

        return $this->render('update', [
            'model' => $model,
            'modelLines' => (empty($modelLines)) ? [new JournalTransLine()] : $modelLines
        ]);
    }

    /**
     * Deletes an existing JournalTrans model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);

        $transaction = Yii::$app->db->beginTransaction();
        try {
            // Delete all related lines first
            JournalTransLine::deleteAll(['journal_trans_id' => $id]);

            // Delete master
            $model->delete();

            $transaction->commit();
            Yii::$app->session->setFlash('success', 'ลบข้อมูลสำเร็จ');
        } catch (Exception $e) {
            $transaction->rollBack();
            Yii::$app->session->setFlash('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }

        return $this->redirect(['index']);
    }

    /**
     * Finds the JournalTrans model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return JournalTrans the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = JournalTrans::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

    /**
     * Function to create multiple models
     */
    protected function createMultiple($modelClass, $multipleModels = [])
    {
        $model = new $modelClass;
        $formName = $model->formName();
        $post = Yii::$app->request->post($formName);
        $models = [];

        if (!empty($multipleModels)) {
            $keys = array_keys(ArrayHelper::map($multipleModels, 'id', 'id'));
            $multipleModels = array_combine($keys, $multipleModels);
        }

        if ($post && is_array($post)) {
            foreach ($post as $i => $item) {
                if (isset($item['id']) && !empty($item['id']) && isset($multipleModels[$item['id']])) {
                    $models[] = $multipleModels[$item['id']];
                } else {
                    $models[] = new $modelClass;
                }
            }
        }

        unset($model, $formName, $post);

        return $models;
    }
}