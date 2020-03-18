<template>
    <div class="row">
        <el-breadcrumb separator="/">
            <el-breadcrumb-item :to="{ path: '/send_template_msg_list' }">群发消息</el-breadcrumb-item>
            <el-breadcrumb-item>群发模板消息</el-breadcrumb-item>
            <el-breadcrumb-item>{{ wechat_name }}</el-breadcrumb-item>
        </el-breadcrumb>
        <el-row>&nbsp</el-row>
        <el-form ref="form_data" :rules="rules" :model="form_data" label-width="120px">
            <el-form-item label="模板选择">
                <el-select v-model="form_data.template_id" placeholder="发布消息对应的战点">
                    <el-option v-for="item in template_list" :label="item.title" :value="item" :key="item.template_id"></el-option>
                </el-select>
            </el-form-item>
            <el-form-item label="" v-if="form_data.template_id">
                <el-card>
                    <el-col :span="6">
                        <span>示例：</span>
                        <el-input type="textarea" :rows="10" v-model="form_data.template_id.example" readonly="readonly"></el-input>
                    </el-col>
                    <el-col :span="6" :offset="1">
                        <span>变量参数：</span>
                        <el-input type="textarea" :rows="10" v-model="form_data.template_id.content" readonly="readonly"></el-input>
                    </el-col>
                    <el-col :span="7" :offset="1">
                        <span>&nbsp</span>
                        <el-input :ref="item+'_class'" v-for="item in form_data.template_id.variable" v-model="form_data.template_var_content[item+'_value']" :placeholder="'请输入'+item+'参数'">
                            <el-select v-model="form_data.template_var_content[item+'_color']" slot="prepend" placeholder="请选择" @change="read_color(form_data.template_var_content[item+'_color'],item)">
                                <el-option label="蓝色" value="#0000FF"></el-option>
                                <el-option label="红色" value="#FF0000"></el-option>
                                <el-option label="浅紫" value="#FF69B4"></el-option>
                                <el-option label="黑色" value="#000"></el-option>
                            </el-select>
                        </el-input>
                    </el-col>
                </el-card>
            </el-form-item>
            <el-form-item label="跳转链接">
                <el-col :span="6">
                    <el-input v-model="form_data.redirect_url"></el-input>
                </el-col>
            </el-form-item>
            <el-form-item label="发送时间" prop="sent_time">
                <el-date-picker  style="width: 400px;" format="yyyy/MM/dd HH:mm:ss" v-model="form_data.sent_time" type="datetime" placeholder="选择发送日期时间" align="right" :picker-options="pickerOptions"></el-date-picker>
            </el-form-item>
            <el-form-item label="测试粉丝id">
                <el-col :span="6">
                    <el-input v-model="form_data.touser" placeholder="如需提前测试，请输入测试粉丝id"></el-input>
                </el-col>
                <el-col :span="6">
                    <el-button type="primary" @click="send_msg_add('form_data','test')">测试发送</el-button>
                </el-col>
            </el-form-item>
            <el-form-item label="群发间隔">
                <el-col :span="6">
                    <el-input v-model="form_data.group_sent_interval_time"></el-input>
                </el-col>
            </el-form-item>
            <el-form-item label="群发标签类型">
                <el-select v-model="form_data.group_tag_type" placeholder="群发类型标签">
                    <el-option label="公众号官方标签" value="1"></el-option>
                </el-select>
            </el-form-item>
            <el-form-item label="公众号官方标签">
                <el-select v-model="form_data.wechat_public_num_id" placeholder="群发类型标签">
                    <el-option label="全部粉丝" value="1"></el-option>
                </el-select>
            </el-form-item>
        </el-form>
        <el-row>&nbsp</el-row>
        <div slot="footer" class="dialog-footer">
            <el-button type="primary" @click="send_msg_add('form_data','all')">添加</el-button>
        </div>
    </div>
</template>
<script>
    export default {
        data:function(){
            return {
                wechat_name:'test',
                form_data:{
                    template_var_content:{},
                    group_tag_type:'1',
                    wechat_public_num_id:'1'
                },
                is_can_submit:true,
                template_list:[],
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
            this.get_template();
            this.is_can_submit = true;
        },
        methods:{
            send_msg_add(formName,test){
                let self = this;
                if(test == 'test'){
                    if(!self[formName].touser || self[formName].touser == ''){
                        self.$message.error({
                            message:'请输入测试粉丝id',
                            duration:5000
                        });
                        return;
                    }
                }
                self.$refs[formName].validate((valid) => {
                    if (valid) {
                        //临时变量，储存转换
                        var template_var_content = self.form_data.template_var_content;
                        var sent_time = self.form_data.sent_time;
                        var template_id = self.form_data.template_id;
                        self[formName].sent_time = self.time_conversion(self[formName].sent_time);
                        self[formName].template_id = self[formName].template_id.template_id;
                        self.form_data.template_var_content = JSON.stringify(self.form_data.template_var_content);
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
                        self.$ajax.post(self.$interfase.add_template_msg,self[formName]).then(function (response) {
                            self.form_data.template_var_content = template_var_content;
                            self.form_data.sent_time = sent_time;
                            self.form_data.template_id = template_id;
                            if (response.data.status == '000') {
                                self.$message({
                                    message:'发送成功',
                                    type:'success',
                                    duration:5000
                                });
                                if(test == 'test'){
                                    self.is_can_submit = true;
                                    return;
                                }else{
                                    self.$router.push('/send_template_msg_list');
                                }
                            }else{
                                self.$message.error({
                                    message:'发送失败,状态码：' + response.data.data.errcode + '，状态信息：' + response.data.data.errmsg,
                                    duration:5000
                                });
                            }
                            self.is_can_submit = true;
                        });
                    } else {
                        console.log('error submit!!');
                        return false;
                    }
                });
            },
            //获取模板id
            get_template(){
                let self = this;
                self.$ajax.post(self.$interfase.get_template).then(function (response) {
                    if (response.data.status == '000') {
                        self.template_list = response.data.data;
                    }
                });
            },
            read_color(color,item){
                let self = this;
                var name = item+'_class';
                self.$refs[item+'_class'][0].$refs.input.style.color = color;
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
