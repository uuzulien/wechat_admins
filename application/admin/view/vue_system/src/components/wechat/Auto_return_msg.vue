<style>
    .box-card{
        margin: 12px;
    }
    .el-table--border th, .el-table__fixed-right-patch{
        border:none;
    }
    .el-table td, .el-table th.is-leaf{
        border: none;
    }
    .el-table th{
        background: none;
    }
    .el-table{
        border: none;
    }
    .el-table, .el-table td, .el-table th{
        position: unset;
    }
    .el-table td, .el-table th{
        height: 20px;
    }
    .box-card .inline_card div{
        display: inline;
    }
</style>

<template>
    <div class="row">
        <el-tabs type="border-card">
            <el-tab-pane label="自动回复列表">
                <el-row type="flex" class="row-bg" justify="end">
                    <el-col :span="6"></el-col>
                    <el-col :span="6"></el-col>
                    <el-col :span="6">
                        <div class="bg-purple">
                            <el-button type="primary" @click="show_table()">添加自动回复</el-button>
                        </div>
                    </el-col>
                </el-row>
                <el-table :data="bindinfo" height="" :span-method="objectSpanMethod" border style="width: 100%" max-height="900">
                    <el-table-column>
                        <template slot-scope="scope">
                            <el-card class="box-card">
                                <div slot="header" class="clearfix">
                                    <el-col :span="6">
                                        <span v-if="scope.row.type == 1">关键字回复：{{ scope.row.keyword}}</span>
                                        <span v-if="scope.row.type == 2">关注自动回复</span>
                                        <span v-if="scope.row.type == 3">统一回复</span>
                                    </el-col>
                                    <el-col :span="12">&nbsp</el-col>
                                    <el-button @click="set_return_msg_status(scope.row.id)" type="primary" v-if="scope.row.status == 1" size="small">
                                        启用中
                                    </el-button>
                                    <el-button @click="set_return_msg_status(scope.row.id)" type="danger" v-if="scope.row.status == 0" size="small">
                                        已弃用
                                    </el-button>
                                    <el-button @click="return_msg_del(scope.row.id)" type="danger" size="small">
                                        删除
                                    </el-button>
                                </div>
                                <div class="inline_card">
                                            <div v-if="scope.row.reply_type == 1">
                                            <span class="text item">回复信息：{{ scope.row.text_reply }}</span>
                                        </div>
                                        <div v-if="scope.row.reply_type == 2">
                                            <span class="text item">回复图片id：{{ scope.row.mediaid_reply}}</span>
                                        </div>
                                        <div v-if="scope.row.reply_type == 3">
                                            <span class="text item">回复音频id：{{ scope.row.mediaid_reply }}</span>
                                        </div>
                                        <div v-if="scope.row.reply_type == 4">
                                            <span class="text item">回复图文id：{{ scope.row.tuwen_reply }}</span>
                                        </div>
                                </div>
                            </el-card>
                        </template>
                    </el-table-column>
                </el-table>
            </el-tab-pane>
        </el-tabs>

        <!--以下添加自动回复之后的弹出层-->
        <el-dialog :visible.sync="infoFormDialog" title="添加自动回复">
            <el-form :model="form_data" ref="form_data" label-width="100px">
                <el-form-item label="类型" prop="type">
                    <el-select v-model="form_data.type" placeholder="请选择">
                        <el-option v-for="item in type" :key="item.value" :label="item.label" :value="item.value"></el-option>
                    </el-select>
                </el-form-item>
                <el-form-item label="回复类型" prop="user_password">
                    <el-select v-model="form_data.reply_type" placeholder="请选择" @change="set_mediaid_reply(form_data.reply_type)">
                        <el-option v-for="item in reply_type" :key="item.value" :label="item.label" :value="item.value"> </el-option>
                    </el-select>
                </el-form-item>
                <div v-if="form_data.type == 1">
                    <el-form-item label="关键字" prop="keyword">
                        <el-col :span="16"><el-input v-model="form_data.keyword"></el-input></el-col>
                    </el-form-item>
                </div>
                <el-form-item label="文本内容" prop="keyword" v-if="form_data.reply_type == 1">
                    <el-col :span="16"><el-input v-model="form_data.text_reply"></el-input></el-col>
                </el-form-item>
                <el-form-item label="图文内容：" prop="keyword" v-else-if="form_data.reply_type == 4">
                    <el-col :span="12">
                        标题:<el-input v-model="form_data.title"></el-input>
                        图片:<el-button @click="showChangeTable(meida_reply_info)">点我选择</el-button><br>
                        描述:<el-input v-model="form_data.des"></el-input>
                        跳转:<el-input v-model="form_data.linkurl"></el-input>
                    </el-col>
                    <img class="img_card"  style="width: 200px; height: 200px;" :src="get_img(preview.msg_content.picurl)"/>
                </el-form-item>
                <el-form-item label="回复内容" v-else>
                    <el-button @click="showChangeTable(meida_reply_info)">点我选择</el-button>
                </el-form-item>
                <!--选择素材之后的展示-->
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
                </div>
            </el-form>
            <div slot="footer" class="dialog-footer">
                <el-button @click="unset_add('form_data')">取 消</el-button>
                <el-button type="primary" @click="return_msg_add('form_data')">添加</el-button>
            </div>
        </el-dialog>

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
                <el-col :span="4" v-if="form_data.reply_type == 4" v-for="item in meida_reply_info"   :offset="1">
                    <el-card >
                        <img   height="120px"  :src="get_img(item.link_url)" @click="set_material_info(item.media_id,item)" class="image">
                    </el-card>
                </el-col>
            </el-row>
        </el-dialog>

    </div>
</template>

<script>
    export default {
        data:function() {
            return {
                //关键字
                keyword:'',
                //控制弹窗开关
                infoFormDialog:false,
                //控制选择弹窗开关
                changFormDialog:false,
                //列表绑定数据
                bindinfo:[],
                //新增数据
                form_data: {
                    'msg_content': {},
                },
                //图片预览
                mediaid_reply:'',
                //回复类型
                type: [
                    {
                        'label' : '关键字回复',
                        'value' : 1,
                    },
                    {
                        'label' : '关注自动回复',
                        'value' : 2,
                    },
                    {
                        'label' : '默认自动回复',
                        'value' : 3,
                    },
                ],
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
                ],
                //预览内容
                preview:{
                    'msg_content': {},
                },
                //样例数据
                tableData: [{
                    date: '2016-05-03',
                    name: '王小虎',
                    address: '上海市普陀区金沙江路 1518 弄'
                },{
                    date: '2016-05-03',
                    name: '王小虎',
                    address: '上海市普陀区金沙江路 1518 弄'
                }]
            };
        },
        created: function () {
            this.reload();
        },
        methods: {
            getCates(){
                let self = this;
                let info = {};

                self.$axios.post(self.$interfase.adminlogin, info).then(function (response){
                    let data = response.data.data;
                    if (data.length > 0) {
                    }
                });
            },
            objectSpanMethod({ row, column, rowIndex, columnIndex }) {
                if (columnIndex == 2) {
                    return {
                        rowspan: 2,
                        colspan: 1
                    };
                }
            },
            //获取自动回复列表
            get_list(){
                let self = this;
                self.$ajax.post(self.$interfase.auto_reply_list).then(function (response){
                    if(response.data.status == '000'){
                        self.bindinfo = response.data.data.list;
                    }
                });
            },
            //触发弹窗
            show_table(){
                this.infoFormDialog = true;
            },
            //设置使用状态
            set_return_msg_status(id){
                let self = this;
                let info = {'id':id};
                self.$ajax.post(self.$interfase.set_auto_reply_status,info).then(function (response){
                    if(response.data.status == '000'){
                        self.$message({
                            message:'操作成功',
                            type:'success'
                        });
                        self.reload();
                    }else{
                        self.$message.error({
                            message:'更换失败',
                        });
                    }
                });
            },
            //删除
            return_msg_del(id){
                let self = this;
                let info = {'id':id};
                self.$ajax.post(self.$interfase.auto_reply_del,info).then(function (response){
                    if(response.data.status == '000'){
                        self.$message({
                            message:'删除成功',
                            type:'success'
                        });
                        self.reload();
                    }else{
                        self.$message.error({
                            message:'删除失败',
                        });
                    }
                });

            },
            //取消
            unset_add(fromName){
                this.infoFormDialog = false;
                this.$refs[formName].resetFields();
            },
            get_material_list(){
                let self = this;
                /*self.$ajax.post(self.$interfase.auto_reply_list).then(function (response){
                    if(response.data.status == '000'){
                        self.bindinfo = response.data.data.list;
                    }
                });*/
            },
            //编辑
            return_msg_edit(id){

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
                    info.file_type = 'image';
                }
                self.$ajax.post(self.$interfase.get_material_list,info).then(function (response) {
                    self.meida_reply_info = {};
                    if (response.data.status == '000') {
                        console.log(response.data.data.list);
                        self.meida_reply_info = response.data.data.list;
                    }
                });
            },
            //添加
            return_msg_add(formName){
                let self = this;
                self.$refs[formName].validate((valid) => {
                    if(valid){
                        self.$ajax.post(self.$interfase.auto_reply_add, self[formName]).then(function (response) {
                            console.log(response);
                            if (response.data.status == '000') {
                                self.$message({
                                    message: '添加成功',
                                    type: 'success'
                                });
                                self.reload();
                            } else {
                                console.log(998);
                                self.$message.error({
                                    message: '添加失败,原因为：'+ response.data.msg,
                                });
                            }
                        });
                    }
                });
            },
            //弹框
            showChangeTable(meida_reply_info){
                this.changFormDialog = true;
            },//输出图片
            get_img(url){
                let self = this;
                return 'http://wechatadmins.weijuli8.com/admin/show_wechat_img?url='+ url;
            },
            //预览内容
            set_preview(type,info){
                let self = this;
                if(type == 2){
                    //图片
                    self.preview = info.link_url;
                }
            },
            set_material_info(media_id,item){
                this.showMaterialInfo = true;
                console.log('选择的mediaid为：'+media_id);
                this.preview.msg_content.picurl = item.link_url;
                console.log(item);
                this.preview.msg_content.voice_name = item.voice_name;
                this.preview.msg_content.voice_create_time = item.create_time;
                this.preview.msg_content.news_datas = item.data;
                this.form_data.mediaid_reply = media_id;
                this.form_data.imgurl = item.link_url;
                this.changFormDialog=false;
            },
            reload(){
                this.get_list();
            }
        }
    }
</script>