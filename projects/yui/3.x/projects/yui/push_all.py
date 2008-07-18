#!/usr/bin/env python
import os

class PushAll(object):
    def __init__(self):
        def pushDir(path):
            masterFile = '/home/y/share/htdocs/Makefile.yimg'
            newFile = os.path.join(path, 'Makefile')
            os.system('ln -s ' + masterFile + ' ' + newFile)
            os.system('cd ' + path + ';pwd;gmake')

            for i in os.listdir(path):
                fullname = os.path.join(path, i)
                if os.path.isdir(fullname):
                    pushDir(fullname)

        pushDir(os.path.abspath(''))


def main():
    PushAll()
           
if __name__ == '__main__':
    main()
