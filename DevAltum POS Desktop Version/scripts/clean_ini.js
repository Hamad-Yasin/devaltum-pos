const fs = require('fs');
const iniPath = 'd:/Projects/POS/Saas Pro/DevAltum POS Desktop Version/runtime/php/php.ini';
let content = fs.readFileSync(iniPath, 'utf8');

content = content.replace(/extension_dir\s*=\s*"[^"]*laragon[^"]*"/gi, 'extension_dir = "ext"');
content = content.replace(/include_path\s*=\s*"[^"]*laragon[^"]*"/gi, ';include_path = "."');
content = content.replace(/error_log\s*=\s*"[^"]*laragon[^"]*"/gi, ';error_log = ""');
content = content.replace(/session\.save_path\s*=\s*"[^"]*laragon[^"]*"/gi, ';session.save_path = ""');
content = content.replace(/sendmail_path\s*=\s*"[^"]*laragon[^"]*"/gi, ';sendmail_path = ""');
content = content.replace(/curl\.cainfo\s*=\s*"[^"]*laragon[^"]*"/gi, ';curl.cainfo = ""');

fs.writeFileSync(iniPath, content, 'utf8');
console.log('php.ini cleaned successfully!');

