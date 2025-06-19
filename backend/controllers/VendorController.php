<?php

namespace backend\controllers;

use backend\models\Vendor;
use backend\models\VendorSearch;
use yii\base\BaseObject;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use Yii;

/**
 * VendorController implements the CRUD actions for Vendor model.
 */
class VendorController extends Controller
{
    /**
     * @inheritDoc
     */
    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            [
                'verbs' => [
                    'class' => VerbFilter::className(),
                    'actions' => [
                        'delete' => ['POST'],
                    ],
                ],
            ]
        );
    }

    /**
     * Lists all Vendor models.
     * @return mixed
     */
    public function actionIndex()
    {
        $pageSize = \Yii::$app->request->post("perpage");
        $searchModel = new VendorSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->setSort(['defaultOrder' => ['id' => SORT_DESC]]);
        $dataProvider->pagination->pageSize = $pageSize;

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'perpage' => $pageSize,
        ]);
    }

    /**
     * Displays a single Vendor model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Vendor model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Vendor();

        if ($this->request->isPost) {
            if ($model->load($this->request->post())) {
                $party_type = 1;
                $address = \Yii::$app->request->post('cus_address');
                $street = \Yii::$app->request->post('cus_street');
                $district_id = \Yii::$app->request->post('district_id');
                $city_id = \Yii::$app->request->post('city_id');
                $province_id = \Yii::$app->request->post('province_id');
                $zipcode = \Yii::$app->request->post('zipcode');

                if($model->save(false)){
                    $address_chk = \common\models\AddressInfo::find()->where(['party_id' => $model->id, 'party_type_id' => $party_type, 'address_type_id' => 1])->one();
                    if ($address_chk) {
                        $address_chk->address = $address;
                        $address_chk->street = $street;
                        $address_chk->district_id = $district_id;
                        $address_chk->city_id = $city_id;
                        $address_chk->province_id = $province_id;
                        $address_chk->zipcode = $zipcode;
                        $address_chk->status = 1;
                        if ($address_chk->save(false)) {

                        }
                    } else {
                        $cus_address = new \common\models\AddressInfo();
                        $cus_address->party_type_id = $party_type;
                        $cus_address->party_id = $model->id;
                        $cus_address->address = $address;
                        $cus_address->street = $street;
                        $cus_address->district_id = $district_id;
                        $cus_address->city_id = $city_id;
                        $cus_address->province_id = $province_id;
                        $cus_address->zipcode = $zipcode;
                        $cus_address->status = 1;
                        $cus_address->address_type_id = 1; // 1 = invoice
                        if ($cus_address->save(false)) {

                        }
                    }
                }
                return $this->redirect(['view', 'id' => $model->id]);
            }
        } else {
            $model->loadDefaultValues();
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Vendor model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($this->request->isPost && $model->load($this->request->post())) {
            $party_type = 1;
            $address = \Yii::$app->request->post('cus_address');
            $street = \Yii::$app->request->post('cus_street');
            $district_id = \Yii::$app->request->post('district_id');
            $city_id = \Yii::$app->request->post('city_id');
            $province_id = \Yii::$app->request->post('province_id');
            $zipcode = \Yii::$app->request->post('zipcode');

            if($model->save(false)){
                $address_chk = \common\models\AddressInfo::find()->where(['party_id' => $model->id, 'party_type_id' => $party_type, 'address_type_id' => 1])->one();
                if ($address_chk) {
                    $address_chk->address = $address;
                    $address_chk->street = $street;
                    $address_chk->district_id = $district_id;
                    $address_chk->city_id = $city_id;
                    $address_chk->province_id = $province_id;
                    $address_chk->zipcode = $zipcode;
                    $address_chk->status = 1;
                    if ($address_chk->save(false)) {

                    }
                } else {
                    $cus_address = new \common\models\AddressInfo();
                    $cus_address->party_type_id = $party_type;
                    $cus_address->party_id = $model->id;
                    $cus_address->address = $address;
                    $cus_address->street = $street;
                    $cus_address->district_id = $district_id;
                    $cus_address->city_id = $city_id;
                    $cus_address->province_id = $province_id;
                    $cus_address->zipcode = $zipcode;
                    $cus_address->status = 1;
                    $cus_address->address_type_id = 1; // 1 = invoice
                    if ($cus_address->save(false)) {

                    }
                }
            }
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Vendor model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Vendor model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Vendor the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Vendor::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
