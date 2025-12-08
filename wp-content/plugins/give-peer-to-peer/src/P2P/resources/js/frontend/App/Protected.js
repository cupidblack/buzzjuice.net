import {cloneElement} from 'react';
import {Redirect, Route} from 'react-router-dom';
import {useSelector} from '@p2p/js/frontend/App/store';

const ProtectedRoute = ({children, component: Component, redirect, checkAccess, ...rest}) => {
    return (
        <Route
            {...rest}
            render={(props) => {
                return checkAccess ? (
                    Component ? (
                        <Component {...props} />
                    ) : (
                        cloneElement(children, props)
                    )
                ) : (
                    <Redirect to={redirect} />
                );
            }}
        />
    );
};

ProtectedRoute.defaultProps = {
    redirect: '/',
};

const FundraiserRoute = (props) => {
    const auth = useSelector((state) => state.auth.fundraiser_id);

    return <ProtectedRoute {...props} checkAccess={auth} />;
};

const RegistrationRoute = (props) => {
    const auth = useSelector((state) => !state.auth.fundraiser_id);

    return <ProtectedRoute {...props} checkAccess={auth} />;
};

const TeamRegistrationRoute = (props) => {
    const auth = useSelector((state) => state.campaign.teams_registration);

    return <ProtectedRoute {...props} checkAccess={auth} />;
};

const GuestRoute = (props) => {
    const auth = useSelector((state) => !state.auth.user_id);

    return <ProtectedRoute {...props} checkAccess={auth} />;
};

export {ProtectedRoute, FundraiserRoute, RegistrationRoute, GuestRoute, TeamRegistrationRoute};
