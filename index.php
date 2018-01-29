<?php 
include_once('pdodb.php');

class M_chuyenbay extends pdodb
{
    public function Doc_chuyenbay()
    {
        $sql = "procDanhSachChuyenBay";
        return $this->all($sql);
    }

    public function Doc_chuyenbay_theo_ma($ma_chuyen_bay)
	{
		$sql = "select * from chuyenbay where id = ?";
		return $this->single($sql, array($ma_chuyen_bay));
	}
}

$m_chuyenbay = new M_chuyenbay();
$datas = $m_chuyenbay->Doc_chuyenbay();
print_r($datas);

echo '<hr/>';
$data = $m_chuyenbay->Doc_chuyenbay_theo_ma(61);
print_r($data);
?>