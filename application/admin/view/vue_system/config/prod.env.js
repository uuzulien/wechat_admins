module.exports = {
  NODE_ENV: '"production"'
}
let argv = process.argv[2] || ''

let merge = require('webpack-merge')
let prodEnv = {
    NODE_ENV: '"production"',
    // API_BASH_PATH: "'http://wechatadmins.weijuli8.com'",
    API_BASH_PATH: "'http://localhost'",
    OUT_DIR:"'dist'",
    PURL:'"https://static.weijuli8.com"',
    UPLOAD_URL:'"https://file.weijuli8.com/upload.php"'
}
module.exports = prodEnv
