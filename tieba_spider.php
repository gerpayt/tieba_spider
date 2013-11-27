<?php
header("Content-type: text/html; charset=utf-8");
//运行时间
@set_time_limit(0);
//输入1024+字节，以防止浏览器缓存
echo str_pad(' ', 1024);
//#关闭并刷新缓存,当然你也可以设置php.ini中output_buffering=0
ob_end_flush();
//贴吧名称
$name = '妖精的尾巴';
$tbname = urlencode(iconv('UTF-8', 'GBK', $name));
//抓取类型 0-按照帖子顺序 1-按照贴图顺序
$type = 0;
//列表页url
$listurltpl = "http://tieba.baidu.com/f?kw=%s" . ($type ? "&tp=1" : "&pn=");
//图册页url
$galleryurltpl = "http://tieba.baidu.com/photo/bw/picture/guide?kw=%s&tid=%s&next=50&alt=jview";
//图片url
$imageurltpl = "http://imgsrc.baidu.com/forum/pic/item/%s.jpg";
//本地的目录
$savepath = "F:/tieba/";
//帖子子文件夹
$filedirtpl = $savepath . "%s/";
//图片文件
$filenametpl = $savepath . "%s/%s.jpg";

$listurl = sprintf($listurltpl, $tbname);
//抓取起始点(50的倍数)
$pn = 0;
//相册最低照片数
$less = 10;
//总页数
$page_count = 100;
//图片总数
$picture_count = 0;
//新增总数
$picture_add = 0;
while (1) {
	if (!$type)
		$listurl = sprintf($listurltpl, $tbname).$pn;
	echo '贴吧页码:'.($pn/50).'   url:'.$listurl.'<br>';
	//得到列表页源代码
	$listhtml = file_get_contents($listurl);
	//匹配出帖子id
	if ($type)
		preg_match_all('/<div class=\"aep_wrapper\" id=\"pic_item_(\d+)\" tid=\"\d+\">/', $listhtml, $m1);
	else
		preg_match_all('/<ul class=\"threadlist_media j_threadlist_media\" id=\"fm(\d+)\"/', $listhtml, $m1);

	//得到帖子id列表
	$tidlist = $m1[1];
	echo "Fetching ... <br /> \r\n";
	foreach ($tidlist as $tid) {
		echo "--Gallery $tid <br /> \r\n";
		//防止url出错,重新生成
		$galleryurl = sprintf($galleryurltpl, $tbname, $tid);
		//得到帖子图册的源代码
		$galleryhtml = file_get_contents($galleryurl);
		$galleryarr = json_decode($galleryhtml, true);
		if ($galleryarr['data']['pic_amount'] < $less) {
			echo '========该相册总数小于 '.$less.' 张,直接跳过========<br>';
			continue;
		}
		//记录总图片数
		$picture_count = $picture_count + $galleryarr['data']['pic_amount'];
		//查看是否已经全部下载
		$filedir = sprintf($filedirtpl, $tid);
		//目录是否存在
		if (!is_dir($filedir))
			mkdir($filedir);
		//已下载数
		$down_count = 0;
		if ($dh = opendir($filedir)) {
			while (($file = readdir($dh)) !== false) {
				if ($file != '.' && $file != '..') {
					$down_count++;
				}
			}
			closedir($dh);
		}
		if ($down_count == $galleryarr['data']['pic_amount']) {
			echo '========该相册已经下载完成========<br>';
			continue;
		}
		//计算翻页次数
		$count = ceil($galleryarr['data']['pic_amount'] / 50);
		for ($i = 0; $i < $count; $i++) {
			echo ('============================================'.$i.'============================================<br>');
			foreach ($galleryarr['data']['pic_list'] as $vo) {
				echo "----Picture {$tid}/{$vo['img']['original']['id']}.jpg ";
				
				$filename = sprintf($filenametpl, $tid, $vo['img']['original']['id']);
				//文件是否存在
				if (!is_file($filename)) {
					$imageurl = sprintf($imageurltpl, $vo['img']['original']['id']);
					//下载图片
					$imagebin = file_get_contents($imageurl);
					//保存图片
					file_put_contents($filename, $imagebin);
					$rnd = rand(2000, 5000);
					echo "Downloaded! ";
					//延时休息
					flush();
					sleep(0.1);
					echo "Sleep $rnd us <br />\r\n";
					$picture_add++;
				}
				else
					echo "Existed! <br />\r\n";
			}
			$galleryurl = sprintf($galleryurltpl, $tbname, $tid).'&pic_id='.$vo['img']['original']['id'];
			$galleryhtml = file_get_contents($galleryurl);
			$galleryarr = json_decode($galleryhtml, true);
		}
	}
	
	//翻到下一页
	if (!$type) {
		$pn += 50;
		//运行结束
		if ($pn > $page_count * 50) {
			exit("下载完成:一共下载 {$page_count} 页, {$picture_count} 张图片, 其中新增 {$picture_add} 张图片");
		}
	}
}
