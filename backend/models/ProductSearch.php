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
    public $globalSearch,$party_id,$warehouse_id,$stock_empty;
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'product_group_id', 'status','product_type_id','type_id','brand_id',], 'integer'],
            [['code', 'name', 'description','party_id','warehouse_id','stock_empty'], 'safe'],
            [['globalSearch'],'string']
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
    public function search($params)
    {
        $query = Product::find();

        // add conditions that should always apply here

        $query->joinWith(['journaltransLine.journalTrans.watchMaker']);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'product_group_id' => $this->product_group_id,
            'product_type_id' => $this->product_type_id,
            'brand_id' => $this->brand_id,
            'type_id' => $this->type_id,
            'status' => $this->status,
        ]);

        if($this->party_id){
            $query->andFilterWhere(['watchmaker.id' => $this->party_id]);
        }

        if($this->warehouse_id){
            $query->andFilterWhere(['journal_trans.warehouse_id' => $this->warehouse_id]);
        }
        if($this->stock_empty == 1){
            $query->andFilterWhere(['stock_qty'=>0]);
        }
        if($this->stock_empty == 2){
            $query->andFilterWhere(['!=','stock_qty',0]);
        }

        if($this->globalSearch != ''){
            $query->andFilterWhere(['like', 'product.name', $this->globalSearch])
                ->orFilterWhere(['like', 'product.description', $this->globalSearch]);
        }


        return $dataProvider;
    }
}
