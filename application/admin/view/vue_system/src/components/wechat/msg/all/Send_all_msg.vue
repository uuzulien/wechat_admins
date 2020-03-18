<template>
    <div class="row">
        <el-breadcrumb separator="/">
            <el-breadcrumb-item :to="{ path: '/send_all_msg_list' }">群发消息</el-breadcrumb-item>
            <el-breadcrumb-item>群发新消息</el-breadcrumb-item>
            <el-breadcrumb-item>{{ wechat_name }}</el-breadcrumb-item>
        </el-breadcrumb>
        <el-row>&nbsp</el-row>
        <el-form ref="form_data" :rules="rules" :model="form_data" label-width="120px">
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
            <el-form-item label="发送时间" prop="sent_time">
                <el-date-picker  style="width: 400px;" v-model="form_data.sent_time" type="datetime" placeholder="选择发送日期时间" align="right" :picker-options="pickerOptions"></el-date-picker>
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
                <div v-if="form_data.reply_type == 1">
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
<!--                    <el-form-item label="回复内容">-->
<!--                        <el-select v-model="form_data.reply_content" placeholder="请选择" >-->
<!--                            <el-option-->
<!--                                    v-for="item in meida_reply_info"-->
<!--                                    :key="item.media_id"-->
<!--                                    :label="item.media_id"-->
<!--                                    :value="item.media_id">-->
<!--                            </el-option>-->
<!--                        </el-select>-->
<!--                    </el-form-item>-->
                </div>
            </el-card>
        </el-form>
        <el-row>&nbsp</el-row>
        <el-dialog :visible.sync="changFormDialog" title="选择素材">
            <el-row>
                <el-col :span="4" v-if="form_data.reply_type == 2" v-for="item in meida_reply_info"   :offset="1">
                    <el-card >
                        <img   height="120px"  :src="get_img(item.link_url)" @click="set_material_info(item.media_id,item)" class="image">
                    </el-card>
                </el-col>
                <div v-if="form_data.reply_type == 3" v-for="items in meida_reply_info">
                    <el-card class="box-card"  style="width:50% !important;">
                        <div class="clearfix" @click="set_material_info(items.media_id,items)" >
                            <p>音频名称：{{ items.voice_name }}</p>
                            <p>添加时间：{{ items.create_time }}</p>
                        </div>
                    </el-card>
                </div>

                <div v-if="form_data.reply_type == 4"  v-for="itemss in meida_reply_info">
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
            <img v-if="form_data.reply_type == 2" class="img_card"  style="width: 200px; height: 200px;" :src="get_img(preview.msg_content.picurl)"/>
            <div v-if="form_data.reply_type == 3">
                <el-card class="box-card"  style="width:50% !important;">
                    <div class="clearfix" >
                        <p>音频名称：{{ preview.msg_content.voice_name }}</p>
                        <p>添加时间：{{ preview.msg_content.voice_create_time }}</p>
                    </div>
                </el-card>
            </div>

            <div v-if="form_data.reply_type == 4" v-for="itemsss in preview.msg_content.news_datas" >
                <img :src="get_img(itemsss.news_thumb_url)" style="width: 200px; height: 200px;"/>
                <div class="bottom font_style">
                    <span>{{ itemsss.news_title }}</span>
                </div>
            </div>
        </div>
        <div slot="footer" class="dialog-footer">
            <el-button type="primary" @click="set_send_all_msg('form_data','add')">添加</el-button>
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
                    group_sent_type:'1',
                    group_sent_tag_type:'1',
                },
                //标签
                tags:[],
                is_can_submit: true,
                showMaterialInfo:false,
                changFormDialog:false,
                //回复方式1:文字/2:图片/3:音频/4:图文
                reply_type: [
                    {
                        'label' : '回复文本',
                        'value' : 1,
                    },
                    {
                        'label' : '回复图片',
                        'value' : 2,
                    },
                    {
                        'label' : '回复音频',
                        'value' : 3,
                    },
                    {
                        'label' : '回复图文',
                        'value' : 4,
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
                //验证规则
                rules: {
                    sent_time:[
                        { required:true,message:'请选择发送时间',trigger:'blur',type:'date' },
                        {
                            validator: (rule, value, callback) => {
                                if(!value) {
                                    return new Error('不能为空');
                                }
                                else {
                                    console.log(value);
                                    var this_time = new Date();
                                    var from_time = new Date(value);
                                    console.log(this_time.getTime());
                                    console.log(from_time.getTime());
                                    console.log(parseInt(this_time.getTime() - from_time.getTime()));
                                    if(this_time.getTime() > from_time.getTime() && parseInt(this_time.getTime() - from_time.getTime()) > 60000) {
                                        callback(new Error('不能选择以往时间'));
                                    }
                                    else{
                                        callback();
                                    }
                                }
                            }
                        }
                    ],
                }
            }
        },
        created:function(){
            this.wechat_name = localStorage.getItem('use_wechat_name');
            this.get_wechat_user_tag_list();
            this.is_can_submit = true;
        },
        methods:{
            goBack(){

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
                        var reply_type = self[formName].reply_type;
                        var sent_time = self[formName].sent_time;
                        var sent_time = self[formName].sent_time;
                        if(self[formName].reply_type == 1){
                            self[formName].reply_type = 'text';
                        }else if(self[formName].reply_type == 2){
                            self[formName].reply_type = 'image';
                        }else if(self[formName].reply_type == 3){
                            self[formName].reply_type = 'voice';
                        }else if(self[formName].reply_type == 4){
                            self[formName].reply_type = 'mpnews';
                        }
                        self[formName].sent_time = self.time_conversion(self[formName].sent_time);
                        var info = {
                            'group_sent_type' : self[formName].group_sent_type,
                            'group_sent_tag_type' : self[formName].group_sent_tag_type,
                            'sent_time' : self[formName].sent_time,
                            'wechat_public_num_id' : self[formName].wechat_public_num_id,
                            'reply_type' : self[formName].reply_type,
                            'reply_content' : self[formName].reply_content,
                        };
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
                        self.$ajax.post(self.$interfase.add_send_wechat_all_msg,info).then(function (response) {
                            if (response.data.status == '000') {
                                self.$message({
                                    message:'添加成功',
                                    type:'success',
                                    duration:5000
                                });
                                if(test == 'test'){
                                    self[formName].reply_type = reply_type;
                                    self[formName].sent_time = sent_time;
                                    self[formName].sent_time = sent_time;
                                    self.is_can_submit = true;
                                    return;
                                }
                                self.$refs[formName].resetFields();
                                self.$router.push('/send_all_msg_list');
                            }else{
                                self.$message.error({
                                    message:'发送失败,状态码：' + response.data.data.errcode + '，状态信息：' + response.data.data.errmsg,
                                    duration:5000
                                });
                                if(test == 'test'){
                                    self[formName].reply_type = reply_type;
                                    self[formName].sent_time = sent_time;
                                    self[formName].sent_time = sent_time;
                                    self.is_can_submit = true;
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
