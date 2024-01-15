#!/usr/bin/python3
#
# Filename  : amail.py
# Author    : J. Veraart
# Date      : 2020-06-16
# Reason    : why not 
#
# To get started just use the next 4 commands :
#
#   Usage information                : ./amail -h
#   Show example command             : ./amail -xmp cmd
#   Show example script sending text : ./amail -xmp text
#   Show example script sending html : ./amail -xmp html
#
# From below you can see that the first 3 parameters are required.
#   recipient, sender and password.
#
# When you have no gmail please specify your smtp server
#
def amail(  recipient
            ,sender
            ,password
            ,ccrecipient    = ''  
            ,bccrecipient   = ''  
            ,subject        = 'No Subject'
            ,body           = ''
            ,bodyfile       = ''
            ,attachments    = ''
            ,smtpserver     = 'smtp.gmail.com'  
            ):

    import smtplib, ssl
    from email.mime.text import MIMEText
    from email.mime.multipart import MIMEMultipart
    from email.mime.base import MIMEBase
    from email import encoders
    import os.path
    from os import path

    msg = MIMEMultipart()
    msg['From']    = sender
    msg['To']      = recipient
    msg["Cc"]      = ccrecipient
    msg["Bcc"]     = bccrecipient
    msg['Subject'] = subject

    recipients = recipient + ',' + ccrecipient + ',' + bccrecipient
    
    if attachments != '':
        for attach in attachments.split(',') :
            attach=attach.strip()
            filename = os.path.basename(attach)
            attachment = open(attach, "rb")
            part = MIMEBase('application', 'octet-stream')
            part.set_payload(attachment.read())
            encoders.encode_base64(part)
            part.add_header('Content-Disposition',
                        f'attachment; filename= {filename}' )
            msg.attach(part)

    if body != '' :
        if body.find('<html>') == 0:
            msg.attach(MIMEText(body, 'html'))
        else:
            msg.attach(MIMEText(body, 'plain'))

    if bodyfile != '' :
        if path.exists(bodyfile):
            if body != '' :
                print(f'body text already in use so send {bodyfile} as attachment')
            with open(bodyfile, 'r') as myfile:
                filebody = myfile.read()
            if filebody.find('<html>') == 0:
                msg.attach(MIMEText(filebody, 'html'))
            else:
                msg.attach(MIMEText(filebody, 'plain'))
        else:
            print('bodyfile does not exist :'+bodyfile)

    if 1 == 1:
     try:
        context = ssl.create_default_context()
        with smtplib.SMTP_SSL(smtpserver, 465, context=context) as server:
            server.login(sender, password )
            server.sendmail(sender, recipients.split(','), msg.as_string())
            server.quit()
     except:
        try:
            server = smtplib.SMTP(smtpserver, 587)
            server.ehlo()
            server.starttls()
            server.login(sender, password )
            server.sendmail(sender, recipients.split(','), msg.as_string())
            server.quit()
        except:
            print("SMPT server connection error")

    return True

import argparse, sys

if len(sys.argv) > 1 :

    parser=argparse.ArgumentParser()

    parser.add_argument('-xmp', help='Examples for command and scripts: \'./amail.py -xmp cmd\', \'./amail.py -xmp text\', \'./amail.py -xmp html\'')
    parser.add_argument('-rcv', help='Required: receiver@someprovider.com[,other@someother.com...]')
    parser.add_argument('-snd', help='Required: sender@someprovider.com')
    parser.add_argument('-pwd', help='Required: sender password')
    parser.add_argument('-ccr', help='Optional: ccreceiver@someprovider.com[,other@someother.com...]')
    parser.add_argument('-bcc', help='Optional: bccreceiver@someprovider.com[,other@someother.com...]')
    parser.add_argument('-sub', help='Optional: subject')
    parser.add_argument('-bdy', help='Optional: body (maybe use \'<html> html message </html>\')')
    parser.add_argument('-bdf', help='Optional: body file in quotes : \'filename\'')
    parser.add_argument('-att', help='Optional: attachment list in quotes : \'fileA, .., fileZ\'')
    parser.add_argument('-srv', help='Optional: smtp server, defaults to smtp.gmail.com')

    args=parser.parse_args()

    if str(args.xmp) in [ 'text' , 'html'] :
        part1="""\
#!/usr/bin/python3
# Example Python script using amail, see amail.py for more parameters
from amail import amail
recipient   = 'Santa@Northpole.com,MrsSanta@Northpole.com'
subject     = 'Dear Mr. and Mrs. Santa, please send us some presents'
wishlists   = 'mywishlist.txt,daddies_wishlist.txt,mommies_wishlist.txt'
sender      = 'Me@MyMailProvider.com'
password    = 'MyMailPassword'"""
        
        part3="""\
amail(  recipient     = recipient  ,subject      = subject
        ,attachments  = wishlists  ,body         = body
        ,sender       = sender     ,password     = password)"""
    
    if str(args.xmp) == 'text':
        part2="""\
body=\"\"\"\\
Dear Mr. and Mrs. Santa,

please send us the presents as mentioned in our wishlists,

many thanks and a happy Christmas,

Me and Rudolph\"\"\""""
    elif str(args.xmp) == 'html':
        part2="""\
body=\"\"\"<html>\\
Dear Mr. and Mrs. Santa,<br><br>
please send us the presents as mentioned in our wishlists,<br><br>
many thanks and a happy Christmas,<br><br>
Me and  <font size=10>🦌</font><br><br>
Favorite Search Engine : 
<a href='https://duckduckgo.com/'><font size=10>🦆🦆Go</font></a>
</html>\"\"\"
# 🦌=deer 🦆=duck, https://unicode.org/emoji/charts/full-emoji-list.html"""

    if str(args.xmp) in [ 'text' , 'html'] :
        print(part1)
        print(part2)
        print(part3)
    elif str(args.xmp) != 'None' :
        print("./amail.py -rcv Santa@Northpole.com -sub 'Dear Santa' -bdy 'Dear Santa, please send us some presents, many thanks and a happy Christmas, Me and Rudolph' -snd Me@MyMailProvider.com -pwd MyMailPassword -att 'mywishlist.txt, daddies_wishlist.txt, mommies_wishlist.txt'")
    else:
        recipient   = args.rcv
        sender      = args.snd
        password    = args.pwd

        if str(args.ccr) == 'None':
            ccrecipient = ''
        else:
            ccrecipient = args.ccr

        if str(args.bcc) == 'None':
            bccrecipient = ''
        else:
            bccrecipient = args.bcc

        if str(args.sub) == 'None':
            subject     = ''
        else:
            subject     = args.sub

        if str(args.bdy) == 'None':
            body = ''
        else:
            body        = args.bdy
            
        if str(args.bdf) == 'None':
            bodyfile = ''
        else:
            bodyfile        = args.bdf
            
        if str(args.att) == 'None':
            attacharray = ''
        else:
            attacharray = args.att
            
        if str(args.srv) == 'None':
            smtpserver = 'smtp.gmail.com'
        else:
            smtpserver = args.srv

        amail(  recipient = recipient
            ,bccrecipient = bccrecipient
            ,ccrecipient  = ccrecipient
            ,sender       = sender
            ,password     = password
            ,subject      = subject
            ,body         = body
            ,bodyfile     = bodyfile
            ,attachments  = attacharray
            ,smtpserver   = smtpserver )
