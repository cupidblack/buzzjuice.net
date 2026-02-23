import {LockIcon, WarningIcon} from '@p2p/Components/Icons';

const {__} = wp.i18n;

const SecureAndEncrypted = () => {
    return (
        <>
            {!!window.GiveP2P.SSL ? (
                <>
                    <LockIcon />
                    <span> {__('Secure and Encrypted', 'give-peer-to-peer')}</span>
                </>
            ) : (
                <>
                    <WarningIcon />
                    <span> {__('Insecure Connection', 'give-peer-to-peer')}</span>
                </>
            )}
        </>
    );
};

export default SecureAndEncrypted;
