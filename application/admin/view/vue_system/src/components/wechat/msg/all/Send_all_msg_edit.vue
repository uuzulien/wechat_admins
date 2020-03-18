<template>
    <div class="row">
        <el-breadcrumb separator="/">
            <el-breadcrumb-item :to="{ path: '/send_all_msg_list' }">群发消息</el-breadcrumb-item>
            <el-breadcrumb-item>群发新消息</el-breadcrumb-item>
            <el-breadcrumb-item>{{ wechat_name }}</el-breadcrumb-item>
        </el-breadcrumb>
        <el-row>&nbsp</el-row>
        <el-form ref="form_data" :model="form_data" label-width="120px">
            <el-form-item label="群发方式">
                <el-select v-model="form_data.group_sent_type" placeholder="群发方式">
                    <el-option label="公众号官方标签" value="1"></el-option>
                </el-select>
            </el-form-item>
            <el-form-item label="群发标签类型">
                <el-select v-model="form_data.group_sent_tag_type" placeholder="群发类型标签">
                    <el-option label="公众号官方标签" value="1"></el-option>
                </el-select>
            </el-form-item>
            <el-form-item label="公众号官方标签">
                <el-select v-model="form_data.wechat_public_num_id" placeholder="标签选择">
                    <el-option label="全部粉丝" value="0"></el-option>
                    <el-option v-for="item in tags"
                               :key="item.id"
                               :label="item.name + '(' + item.count + ')'"
                               :value="item.id">
                    </el-option>
                </el-select>
            </el-form-item>
            <el-form-item label="发送时间">
                <el-date-picker v-model="form_data.sent_time" type="datetime" placeholder="选择发送日期时间" align="right" :picker-options="pickerOptions"></el-date-picker>
            </el-form-item>
            <el-form-item label="测试粉丝ID">
                <el-col :span="12">
                    <el-input v-model="form_data.touser" placeholder="请填入测试的粉丝id"></el-input>
                </el-col>
                <el-col :span="12">
                    <el-button type="primary" @click="set_send_all_msg('form_data','test')">发送测试</el-button>
                </el-col>
            </el-form-item>
            <el-card class="box-card"  style="width:50% !important;">
                <el-form-item label="回复类型" prop="reply_type">
                    <el-select v-model="form_data.reply_type" placeholder="请选择" @change="set_mediaid_reply(form_data.reply_type)">
                        <el-option v-for="item in reply_type" :key="item.value" :label="item.label" :value="item.value"> </el-option>
                    </el-select>
                </el-form-item>
                <div v-if="form_data.reply_type == 'text'">
                    <el-form-item label="文本内容" prop="keyword">
                        <el-col :span="24">
                            <el-input :autosize="{ minRows: 30, maxRows: 30}" @blur="checkhtmlatag(form_data.reply_content)" type="textarea" v-model="form_data.reply_content"></el-input>
                        </el-col>
                    </el-form-item>
                </div>
                <div v-else>
                    <el-form-item label="回复内容" >
                        <el-button @click="showChangeTable(meida_reply_info)">点我选择</el-button>
                    </el-form-item>
                </div>
            </el-card>
        </el-form>
        <el-row>&nbsp</el-row>

        <el-row v-if="showMaterialInfo == false">

            <img v-if="form_data.reply_type == 'image'" class="img_card"  style="width: 200px; height: 200px;" :src="get_img(preview.msg_content.picurl)"/>
            <div v-if="form_data.reply_type == 'voice'">
                <el-card class="box-card"  style="width:50% !important;">
                    <div class="clearfix" >
                        <p>音频名称：{{ preview.msg_content.voice_name }}</p>
                        <p>添加时间：{{ preview.msg_content.voice_create_time }}</p>
                    </div>
                </el-card>
            </div>

            <div v-if="form_data.reply_type == 'mpnews'" v-for="itemsss in preview.msg_content.news_datas" >
                <img :src="get_img(itemsss.news_thumb_url)" style="width: 200px; height: 200px;"/>
                <div class="bottom font_style">
                    <span>{{ itemsss.news_title }}</span>
                </div>
            </div>
        </el-row>
        <el-dialog :visible.sync="changFormDialog" title="选择素材">
            <el-row>
                <el-col :span="4" v-if="form_data.reply_type == 'image'" v-for="item in meida_reply_info"   :offset="1">
                    <el-card >
                        <img   height="120px"  :src="get_img(item.link_url)" @click="set_material_info(item.media_id,item)" class="image">
                    </el-card>
                </el-col>
                <div v-if="form_data.reply_type == 'voice'" v-for="items in meida_reply_info">
                    <el-card class="box-card"  style="width:50% !important;">
                        <div class="clearfix" @click="set_material_info(items.media_id,items)" >
                            <p>音频名称：{{ items.voice_name }}</p>
                            <p>添加时间：{{ items.create_time }}</p>
                        </div>
                    </el-card>
                </div>

                <div v-if="form_data.reply_type == 'mpnews'"  v-for="itemss in meida_reply_info">
                    <el-card class="box-card"  style="width:50% !important;">
                        <div v-for="itemsss in itemss.data" @click="set_material_info(itemss.media_id,itemss)">
                            <img :src="get_img(itemsss.news_thumb_url)"/>
                            <div class="bottom font_style">
                                <span>{{ itemsss.news_title }}</span>
                            </div>
                        </div>
                    </el-card>
                </div>
            </el-row>
        </el-dialog>
        <div v-if="showMaterialInfo">
            <img v-if="form_data.reply_type == 'image'" class="img_card"  style="width: 200px; height: 200px;" :src="get_img(preview.msg_content.picurl)"/>
            <div v-if="form_data.reply_type == 'voice'">
                <el-card class="box-card"  style="width:50% !important;">
                    <div class="clearfix" >
                        <p>音频名称：{{ preview.msg_content.voice_name }}</p>
                        <p>添加时间：{{ preview.msg_content.voice_create_time }}</p>
                    </div>
                </el-card>
            </div>

            <div v-if="form_data.reply_type == 'mpnews'" v-for="itemsss in preview.msg_content.news_datas" >
                <img :src="get_img(itemsss.news_thumb_url)" style="width: 200px; height: 200px;"/>
                <div class="bottom font_style">
                    <span>{{ itemsss.news_title }}</span>
                </div>
            </div>
        </div>
        <div slot="footer" class="dialog-footer">
            <el-button type="primary" @click="set_send_all_msg('form_data','add')">修改</el-button>
        </div>
    </div>
</template>
<script>
    export default {
        data:function(){
            return {
                wechat_name:'',
                //列表绑定数据
                form_data:{
                    id:'',
                    group_sent_type:'',
                    group_sent_tag_type:'',
                    wechat_public_num_id:'',
                    sent_time:'',
                    msg_content: {},
                    reply_type:'',
                },
                //标签
                tags:[],
                is_can_submit : true,
                showMaterialInfo:false,
                changFormDialog:false,
                //回复方式1:文字/2:图片/3:音频/4:图文
                reply_type: [
                    {
                        'label' : '回复文本',
                        'value' : 'text',
                    },
                    {
                        'label' : '回复图片',
                        'value' : 'image',
                    },
                    {
                        'label' : '回复音频',
                        'value' : 'voice',
                    },
                    {
                        'label' : '回复图文',
                        'value' : 'mpnews',
                    },
                ],
                //回复内容列表
                meida_reply_info:[
                    {
                        'media_id':''
                    }
                ],//弹框
                //预览内容
                preview:{
                    'msg_content': {},
                },
                showChangeTable(meida_reply_info){
                    this.changFormDialog = true;
                },//输出图片
                pickerOptions: {
                    shortcuts: [{
                        text: '10分钟后',
                        onClick(picker) {
                            const date = new Date();
                            date.setTime(date.getTime() + 3600 * 1000 * 0.17);
                            picker.$emit('pick', date);
                        }
                    }, {
                        text: '30分钟后',
                        onClick(picker) {
                            const date = new Date();
                            date.setTime(date.getTime() + 3600 * 1000 * 0.5);
                            picker.$emit('pick', date);
                        }
                    }, {
                        text: '1小时后',
                        onClick(picker) {
                            const date = new Date();
                            date.setTime(date.getTime() + 3600 * 1000);
                            picker.$emit('pick', date);
                        }
                    }, {
                        text: '2小时后',
                        onClick(picker) {
                            const date = new Date();
                            date.setTime(date.getTime() + 3600 * 1000 * 2);
                            picker.$emit('pick', date);
                        }
                    }, {
                        text: '3小时后',
                        onClick(picker) {
                            const date = new Date();
                            date.setTime(date.getTime() + 3600 * 1000 * 3);
                            picker.$emit('pick', date);
                        }
                    }]
                },
            }
        },
        created:function(){
            this.wechat_name = localStorage.getItem('use_wechat_name');
            this.get_wechat_user_tag_list();
            this.get_info();
            this.is_can_submit = true;
        },
        methods:{
            get_info(){
                let self = this;
                var id = self.$route.params.id;
                var type = self.$route.params.type;
                //存在传参则取相应的数据
                if(id){
                    self.$ajax.post(self.$interfase.get_msg_info,{'id':id,'type':type}).then(function (response) {
                        console.log(response.data);
                        if (response.data.status == '000') {
                            self.form_data.id = id;
                            self.form_data.reply_type = response.data.data.reply_type;
                            self.form_data.group_sent_type = response.data.data.group_sent_type.toString();
                            self.form_data.group_sent_tag_type = response.data.data.group_sent_tag_type.toString();
                            self.form_data.wechat_public_num_id = response.data.data.wechat_public_num_id.toString();
                            if(self.form_data.reply_type == 'text'){
                            }else if(self.form_data.reply_type == 'image'){
                                self.preview.msg_content.picurl = response.data.data.material_url;
                                self.set_mediaid_reply(2);
                            }else if(self.form_data.reply_type == 'voice'){
                                self.preview.msg_content.voice_name = response.data.data.voice_name;
                                self.preview.msg_content.voice_create_time = response.data.data.create_time;
                                self.set_mediaid_reply(3);
                            }else if(self.form_data.reply_type == 'mpnews'){
                                self.preview.msg_content.news_datas = response.data.data.news_data;
                                self.set_mediaid_reply(4);
                            }
                            self.form_data.reply_content = response.data.data.reply_content;
                            let date = new Date();
                            self.form_data.sent_time = date.setTime(response.data.data.sent_time * 1000);
                        }else{
                            self.$message.error({
                                message:'操作失败:'+response.data.msg,
                            });
                        }
                    });
                }
            },
            //获取公众号标签列表
            get_wechat_user_tag_list(){
                let self = this;
                self.$ajax.post(self.$interfase.get_wechat_user_tag_list).then(function (response){
                    if(response.data.status == '000'){
                        self.tags = response.data.data;
                    }else{

                    }
                });
            },
            get_img(url){
                let self = this;
                return 'http://wechatadmins.weijuli8.com/admin/show_wechat_img?url='+ url;
            },
            //素材列表
            set_mediaid_reply(status){
                //重新选择的时候清空media_id
                this.preview.msg_content.media_id = '';
                this.showMaterialInfo = false;
                let self = this;
                let info = {'file_type' : ''};
                if(status == 1){
                    return;
                }else if(status == 2){
                    info.file_type = 'image';
                }else if(status == 3){
                    info.file_type = 'voice';
                }else if(status == 4){
                    info.file_type = 'news';
                }else{
                    if(status == 'mpnews'){
                        status = 'news';
                    }
                    info.file_type = status;
                }
                self.$ajax.post(self.$interfase.get_material_list,info).then(function (response) {
                    if (response.data.status == '000') {
                        self.meida_reply_info = response.data.data.list;
                    }
                });
            },
            //设置
            set_send_all_msg(formName,test){
                let self = this;
                self.$refs[formName].validate((valid) => {
                    if (valid) {
                        self[formName].sent_time = self.time_conversion(self[formName].sent_time);
                        var info = {
                            'group_sent_type' : self[formName].group_sent_type,
                            'group_sent_tag_type' : self[formName].group_sent_tag_type,
                            'sent_time' : self[formName].sent_time,
                            'wechat_public_num_id' : self[formName].wechat_public_num_id,
                            'reply_type' : self[formName].reply_type,
                            'reply_content' : self[formName].reply_content,
                        };
                        info.id = self[formName].id;
                        if(test == 'test'){
                            info.is_test = 1;
                            info.touser = self[formName].touser;
                        }
                        /***************以防用户连续点击多次**************/
                        if(self.is_can_submit === false){
                            self.$message.error({
                                message:'请等待上一步操作提交完成',
                                duration:5000
                            });
                            return;
                        }
                        /***************结束**************/
                        self.is_can_submit = false;
                        self.$ajax.post(self.$interfase.send_all_msg_update,info).then(function (response) {
                            if (response.data.status == '000') {
                                self.$message({
                                    message:'修改成功',
                                    type:'success',
                                    duration:5000
                                });
                                if(test == 'test'){
                                    return;
                                }
                                self.$refs[formName].resetFields();
                                self.$router.push('/send_all_msg_list');
                            }else{
                                if(response.data.data.errcode == undefined){
                                    self.$message.error({
                                        message:response.data.msg,
                                        duration:5000
                                    });
                                }else{
                                    self.$message.error({
                                        message:'发送失败,状态码：' + response.data.data.errcode + '，状态信息：' + response.data.data.errmsg,
                                        duration:5000
                                    });
                                }
                                if(test == 'test'){
                                    return;
                                }
                                self.$refs[formName].resetFields();
                            }
                            self.is_can_submit = true;
                        });
                    } else {
                        console.log('error submit!!');
                        return false;
                    }
                });
            },
            checkhtmlatag(str){
                let self = this;
                let info = {
                    'str' : str,
                };
                self.$ajax.post(self.$interfase.checkhtmlaTag,info).then(function (response) {
                    if(response.data.status ==  '000'){
                        self.$message({
                            message:'文本检测通过',
                            type:'success'
                        });
                    }else{
                        self.$message.error({
                            message:'文本检测未通过'
                        });
                    }
                });
            },
            set_material_info(media_id,item){
                this.showMaterialInfo = true;
                console.log('选择的mediaid为：'+media_id);
                this.preview.msg_content.picurl = item.link_url;
                console.log(item);
                this.preview.msg_content.voice_name = item.voice_name;
                this.preview.msg_content.voice_create_time = item.create_time;
                this.preview.msg_content.news_datas = item.data;
                this.form_data.reply_content = media_id;
                this.changFormDialog=false;
            },
            time_conversion(dates){
                if(dates != undefined){
                    let s = new Date(dates);
                    let date = s.getFullYear() + '-' + (s.getMonth() + 1) + '-' + s.getDate() + ' '+ s.getHours() + ':' + s.getMinutes() + ':' + s.getSeconds();
                    return date;
                }else{
                    return '';
                }
            },
        }
    }
</script>
