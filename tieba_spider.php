<?php
//运行时间
@set_time_limit(60);
//贴吧名称
$tbname = "%CD%BC%C6%AC";
//抓取类型 0-按照帖子顺序 1-按照贴图顺序
$type = 0;
//列表页url
$listurltpl = "http://tieba.baidu.com/f?kw=%s".($type?"&tp=1":"&pn=");
//图册页url
$galleryurltpl = "http://tieba.baidu.com/photo/bw/picture/guide?kw=%s&tid=%s&next=9999";
//图片url
$imageurltpl = "http://imgsrc.baidu.com/forum/pic/item/%s.jpg";
//本地的目录
$savepath = "h:/images/";
//帖子子文件夹
$filedirtpl = $savepath."%s/";
//图片文件
$filenametpl = $savepath."%s/%s.jpg";

$listurl = sprintf($listurltpl,$tbname);
//抓取起始点
$pn = 0;
while(1)
{
    if (!$type) $listurl .= $pn;
	//得到列表页源代码
	$listhtml = file_get_contents($listurl);
	//匹配出帖子id
	if($type)
		preg_match_all('/<div class=\"aep_wrapper\" id=\"pic_item_(\d+)\" tid=\"\d+\">/',$listhtml,$m1);
	else
		preg_match_all('/<ul class=\"threadlist_media j_threadlist_media\" id=\"fm(\d+)\"/',$listhtml,$m1);
	//得到帖子id列表
	$tidlist = $m1[1];
	echo "Fetching ... <br /> \r\n";
	foreach($tidlist as $tid)
	{
		echo "--Gallery $tid <br /> \r\n";
		$galleryurl = sprintf($galleryurltpl,$tbname,$tid);
		//得到帖子图册的源代码
		$galleryhtml = file_get_contents($galleryurl);
		//匹配出图片id
		preg_match_all('/\{\"original\":\{\"id\":\"(\w+)\"/',$galleryhtml,$m2);
		//得到图片id列表
		$pidlist = $m2[1];
		foreach($pidlist as $pid)
		{
			echo "----Picture {$tid}/{$pid}.jpg ";
			$filedir = sprintf($filedirtpl,$tid);
			$filename = sprintf($filenametpl,$tid,$pid);
			//文件是否存在
			if(!is_file($filename))
			{
				$imageurl = sprintf($imageurltpl,$pid);
				//下载图片
				$imagebin = file_get_contents($imageurl);
				//目录是否存在
				if(!is_dir($filedir))
					mkdir($filedir);
				//保存图片
				file_put_contents($filename,$imagebin);
				$rnd = rand(2000,5000);
				echo "Downloaded! ";
				//延时休息
				sleep(1.0*$rnd/1000);
				echo "Sleep $rnd us <br />\r\n";
			}
			else
				echo "Existed! <br />\r\n";
		}
	}
	//翻到下一页
	if (!$type) $pn += 50;
}
