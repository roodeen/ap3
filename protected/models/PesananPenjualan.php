<?php

/**
 * This is the model class for table "pesanan_penjualan".
 *
 * The followings are the available columns in table 'pesanan_penjualan':
 * @property string $id
 * @property string $nomor
 * @property string $tanggal
 * @property string $profil_id
 * @property string $penjualan_id
 * @property integer $status
 * @property string $updated_at
 * @property string $updated_by
 * @property string $created_at
 *
 * The followings are the available model relations:
 * @property Penjualan $penjualan
 * @property User $updatedBy
 * @property PesananPenjualanDetail[] $pesananPenjualanDetails
 */
class PesananPenjualan extends CActiveRecord
{

    const STATUS_DRAFT = 0;
    const STATUS_PESAN = 10;
    const STATUS_BATAL = 20;
    const STATUS_JUAL  = 30;

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'pesanan_penjualan';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return [
            ['profil_id', 'required'],
            ['status', 'numerical', 'integerOnly' => true],
            ['nomor', 'length', 'max' => 45],
            ['profil_id, penjualan_id, updated_by', 'length', 'max' => 10],
            ['tanggal, created_at, updated_at, updated_by', 'safe'],
            // The following rule is used by search().
            // @todo Please remove those attributes that should not be searched.
            ['id, nomor, tanggal, profil_id, penjualan_id, status, updated_at, updated_by, created_at', 'safe', 'on' => 'search'],
        ];
    }

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return [
            'penjualan'               => [self::BELONGS_TO, 'Penjualan', 'penjualan_id'],
            'updatedBy'               => [self::BELONGS_TO, 'User', 'updated_by'],
            'pesananPenjualanDetails' => [self::HAS_MANY, 'PesananPenjualanDetail', 'pesanan_penjualan_id'],
        ];
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return [
            'id'           => 'ID',
            'nomor'        => 'Nomor',
            'tanggal'      => 'Tanggal',
            'profil_id'    => 'Profil',
            'penjualan_id' => 'Penjualan',
            'status'       => 'Status',
            'updated_at'   => 'Updated At',
            'updated_by'   => 'Updated By',
            'created_at'   => 'Created At',
        ];
    }

    /**
     * Retrieves a list of models based on the current search/filter conditions.
     *
     * Typical usecase:
     * - Initialize the model fields with values from filter form.
     * - Execute this method to get CActiveDataProvider instance which will filter
     * models according to data in model fields.
     * - Pass data provider to CGridView, CListView or any similar widget.
     *
     * @return CActiveDataProvider the data provider that can return the models
     * based on the search/filter conditions.
     */
    public function search()
    {
        // @todo Please modify the following code to remove attributes that should not be searched.

        $criteria = new CDbCriteria;

        $criteria->compare('id', $this->id);
        $criteria->compare('nomor', $this->nomor, true);
        $criteria->compare('tanggal', $this->tanggal, true);
        $criteria->compare('profil_id', $this->profil_id);
        $criteria->compare('penjualan_id', $this->penjualan_id);
        $criteria->compare('status', $this->status);
        $criteria->compare('updated_at', $this->updated_at, true);
        $criteria->compare('updated_by', $this->updated_by, true);
        $criteria->compare('created_at', $this->created_at, true);

        return new CActiveDataProvider($this, [
            'criteria' => $criteria,
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return PesananPenjualan the static model class
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function beforeSave()
    {

        if ($this->isNewRecord) {
            $this->created_at = date('Y-m-d H:i:s');
            $this->tanggal = date('Y-m-d H:i:s');
        }
        $this->updated_at = null; // Trigger current timestamp
        $this->updated_by = Yii::app()->user->id;
        return parent::beforeSave();
    }

    /**
     * Mencari nomor untuk penomoran surat
     * @return int maksimum+1 atau 1 jika belum ada nomor untuk tahun ini
     */
    public function cariNomor()
    {
        $tahun = date('y');
        $data  = $this->find([
            'select'    => 'max(substring(nomor,9)*1) as max',
            'condition' => "substring(nomor,5,2)='{$tahun}'"]
        );

        $value = is_null($data) ? 0 : $data->max;
        return $value + 1;
    }

    /**
     * Membuat nomor surat
     * @return string Nomor sesuai format "[KodeCabang][kodeDokumen][Tahun][Bulan][SequenceNumber]"
     */
    public function generateNomor()
    {
        $config         = Config::model()->find("nama='toko.kode'");
        $kodeCabang     = $config->nilai;
        $kodeDokumen    = KodeDokumen::PESANAN_PENJUALAN;
        $kodeTahunBulan = date('ym');
        $sequence       = substr('00000' . $this->cariNomor(), -6);
        return "{$kodeCabang}{$kodeDokumen}{$kodeTahunBulan}{$sequence}";
    }

    public function beforeValidate()
    {
        $this->profil_id = empty($this->profil_id) ? Profil::PROFIL_UMUM : $this->profil_id;
        return parent::beforeValidate();
    }

}
