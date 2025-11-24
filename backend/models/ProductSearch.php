<?php

namespace backend\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\Product;

/**
 * ProductSearch represents the model behind the search form of `backend\models\Product`.
 */
class ProductSearch extends Product
{
    public $globalSearch,$party_id,$warehouse_id,$stock_empty,$perpage;
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'product_group_id', 'status','product_type_id','type_id','brand_id',], 'integer'],
            [['code', 'name', 'description','party_id','warehouse_id','stock_empty',], 'safe'],
            [['globalSearch'],'string'],
            [['perpage'],'integer']
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
//    public function search($params)
//    {
//        $query = Product::find();
//
//        // add conditions that should always apply here
//
//        $query->joinWith(['journaltransLine.journalTrans.watchMaker'])->distinct();
//
//        $dataProvider = new ActiveDataProvider([
//            'query' => $query,
//            'pagination' => [
//                'pageSize' => isset($params['perpage']) ? (int)$params['perpage'] : 20,
//                'params' => $params, // สำคัญ: ส่ง params ทั้งหมด
//            ],
//        ]);
//
//        $this->load($params);
//
//        //  print_r($params);
//
//        if (!$this->validate()) {
//            // uncomment the following line if you do not want to return any records when validation fails
//            // $query->where('0=1');
//            return $dataProvider;
//        }
//
//        // grid filtering conditions
//        $query->andFilterWhere([
//            'id' => $this->id,
//            'product_group_id' => $this->product_group_id,
//            'product_type_id' => $this->product_type_id,
//            'brand_id' => $this->brand_id,
//            'type_id' => $this->type_id,
//            'status' => $this->status,
//        ]);
//
//        if($this->party_id){
//            $query->andFilterWhere(['watchmaker.id' => $this->party_id]);
//        }
//
//        if($this->warehouse_id){
//            $query->andFilterWhere(['journal_trans_line.warehouse_id' => $this->warehouse_id]);
//        }
//        if($this->stock_empty == 1){
//            $query->andFilterWhere(['stock_qty'=>0]);
//        }
//        if($this->stock_empty == 2){
//            $query->andFilterWhere(['!=','stock_qty',0]);
//        }
//
//        // แก้ไขส่วน globalSearch ให้ใช้ andWhere แทน orFilterWhere
//        if($this->globalSearch != ''){
//            $query->andWhere([
//                'or',
//                ['like', 'product.name', $this->globalSearch],
//                ['like', 'product.description', $this->globalSearch]
//            ]);
//        }
//
//        return $dataProvider;
//    }

    public function search($params)
    {
        // สร้าง subquery เพื่อดึง product_id ที่ไม่ซ้ำพร้อมเงื่อนไข join
        $subQuery = Product::find()
            ->select('product.id')
            ->joinWith(['journaltransLine.journalTrans.watchMaker'])
            ->distinct();

        // ใช้ main query โดยอ้างอิง subquery
        $query = Product::find()
            ->where(['id' => $subQuery]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => isset($params['perpage']) ? (int)$params['perpage'] : 20,
            ],
            'sort' => [
                'defaultOrder' => ['id' => SORT_DESC] // กำหนดการเรียงลำดับที่ชัดเจน
            ]
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        // ย้าย filter conditions ไปที่ subQuery แทน
        $subQuery->andFilterWhere([
            'product.product_group_id' => $this->product_group_id,
            'product.product_type_id' => $this->product_type_id,
            'product.brand_id' => $this->brand_id,
            'product.type_id' => $this->type_id,
            'product.status' => $this->status,
        ]);

        if($this->party_id){
            $subQuery->andFilterWhere(['watchmaker.id' => $this->party_id]);
        }

        if($this->warehouse_id){
            $subQuery->andFilterWhere(['journal_trans_line.warehouse_id' => $this->warehouse_id]);
        }

        // stock_empty filter ใน main query
        if($this->stock_empty == 1){
            $query->andFilterWhere(['stock_qty' => 0]);
        }
        if($this->stock_empty == 2){
            $query->andFilterWhere(['!=', 'stock_qty', 0]);
        }

        if($this->globalSearch != ''){
            $query->andWhere([
                'or',
                ['like', 'name', $this->globalSearch],
                ['like', 'description', $this->globalSearch]
            ]);
        }

        return $dataProvider;
    }
}