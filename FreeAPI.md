### API免费接口
#### https://www.bejson.com/knownjson/webInterface/
```html
JSON API免费接口
电商接口
淘宝商品搜索建议: 
http://suggest.taobao.com/sug?code=utf-8&q=商品关键字&callback=cb
PS：callback是回调函数设定

物流接口
快递接口: 
http://www.kuaidi100.com/query?type=快递公司代号&postid=快递单号
PS：快递公司编码:申通="shentong" EMS="ems" 顺丰="shunfeng" 圆通="yuantong" 中通="zhongtong" 韵达="yunda" 天天="tiantian" 汇通="huitongkuaidi" 全峰="quanfengkuaidi" 德邦="debangwuliu" 宅急送="zhaijisong"

谷歌接口
FeedXml转json接口: 
http://ajax.googleapis.com/ajax/services/feed/load?q=Feed地址&v=1.0
备选参数callback：&callback=foo就会在json外面嵌套foo({})方便做jsonp使用。
备选参数n：返回多少条记录。

百度接口
百度百科接口: 
http://baike.baidu.com/api/openapi/BaikeLemmaCardApi?scope=103&format=json&appid=379020&bk_key=关键字&bk_length=600
查询出错示例如下：查看原始页面 {"error_code":"20000","error_msg":"search word not found"}

天气接口
百度接口: 
http://api.map.baidu.com/telematics/v3/weather?location=嘉兴&output=json&ak=5slgyqGDENN7Sy7pw29IUvrZ
PS：location:城市名或经纬度 ak:开发者密钥 output:默认xml

新浪接口: 
http://php.weather.sina.com.cn/iframe/index/w_cl.php?code=js&day=0&city=&dfc=1&charset=utf-8
```

#### https://www.free-api.com/
#### https://api.uomg.com/
#### https://api.isoyu.com/#/
#### https://www.postman.com/postman/workspace/published-postman-templates/documentation/631643-f695cab7-6878-eb55-7943-ad88e1ccfd65?ctx=documentation
